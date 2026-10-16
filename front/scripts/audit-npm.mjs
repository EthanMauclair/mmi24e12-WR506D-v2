/**
 * npm audit, avec la liste des failles acceptées (D-033, D-034, audit.md).
 *
 * `npm audit` échoue dès qu'une faille est signalée, y compris celles que nous avons acceptées faute de
 * correctif : la CI serait toujours rouge. `--audit-level=critical` la ferait passer, mais laisserait aussi
 * passer toute nouvelle faille haute. Ici, on échoue sur toute faille haute ou critique absente de la liste.
 *
 * Lancement : make audit (ou, dans le conteneur front, node scripts/audit-npm.mjs).
 */
import { execFileSync } from 'node:child_process'

/** Failles acceptées, avec leur justification. Toute nouvelle entrée passe par le journal de décisions. */
const ACCEPTEES = {
  // node-forge ≤ 1.4.0 : certificat HTTPS du serveur de développement uniquement ; vérification de signature inutilisée.
  'GHSA-86w9-cpqp-85rv': 'node-forge (D-033)',
  // braces ≤ 3.0.3 : motifs de fichiers écrits par nous, à la compilation, jamais saisis par un utilisateur.
  'GHSA-vfj7-8cjw-p6xm': 'braces (D-033)',
}
const BLOQUANTES = ['high', 'critical']

let sortie
try {
  sortie = execFileSync('npm', ['audit', '--omit=dev', '--json'], { encoding: 'utf8', maxBuffer: 20 * 1024 * 1024 })
}
catch (erreur) {
  // npm audit sort en erreur dès qu'il trouve une faille : le rapport est quand même sur la sortie standard.
  sortie = erreur.stdout
}

let rapport
try {
  rapport = JSON.parse(sortie)
}
catch {
  console.error('npm audit n\'a pas renvoyé de rapport lisible :\n' + sortie)
  process.exit(1)
}
if (rapport.error) {
  console.error(`npm audit a échoué : ${rapport.error.summary ?? JSON.stringify(rapport.error)}`)
  process.exit(1)
}

// Chaque faille d'origine (avec son identifiant GHSA), une seule fois, même si plusieurs paquets en dépendent.
const failles = new Map()
for (const [paquet, vulnerabilite] of Object.entries(rapport.vulnerabilities ?? {})) {
  for (const source of vulnerabilite.via) {
    if (typeof source === 'object' && BLOQUANTES.includes(source.severity)) {
      const id = source.url?.split('/').pop() ?? source.title
      failles.set(id, { paquet, gravite: source.severity, titre: source.title })
    }
  }
}

const nouvelles = [...failles].filter(([id]) => !(id in ACCEPTEES))
const acceptees = [...failles].filter(([id]) => id in ACCEPTEES)
const disparues = Object.keys(ACCEPTEES).filter(id => !failles.has(id))

const total = rapport.metadata?.vulnerabilities ?? {}
console.log(`npm audit : ${total.critical ?? 0} critique(s), ${total.high ?? 0} haute(s), ${total.moderate ?? 0} moyenne(s) (paquets dépendants compris).`)
for (const [id, faille] of acceptees) {
  console.log(`  acceptée  ${id}  ${faille.paquet}  ${faille.gravite}  (${ACCEPTEES[id]})`)
}
for (const id of disparues) {
  console.log(`  corrigée  ${id}  ${ACCEPTEES[id]} : n'est plus signalée, à retirer de la liste`)
}

if (nouvelles.length > 0) {
  for (const [id, faille] of nouvelles) {
    console.error(`  NOUVELLE  ${id}  ${faille.paquet}  ${faille.gravite}  ${faille.titre}`)
  }
  console.error(`${nouvelles.length} faille(s) haute(s) ou critique(s) non acceptée(s) : corriger, ou argumenter dans le journal de décisions.`)
  process.exit(1)
}
console.log('Aucune faille haute ou critique en dehors de celles acceptées.')
