# PENDERIE – commandes du projet (séance 15).
# Tout passe par Docker : rien d'autre à installer que Docker et make.
# « make » seul affiche la liste des commandes.

DC  = docker compose
PHP = $(DC) exec -T php

.DEFAULT_GOAL := aide
.PHONY: aide demarrer front arreter installer migrer cache \
        tests tests-unitaires tests-fonctionnels couverture \
        phpstan cs cs-corriger lint audit qualite

## —— Projet ————————————————————————————————————————————
aide: ## Affiche cette aide
	@grep -E '(^[a-z-]+:.*?## .*$$)|(^## )' $(MAKEFILE_LIST) \
		| sed -e 's/^## /\n/' \
		| awk 'BEGIN {FS = ":.*?## "} /:/ {printf "  \033[32m%-20s\033[0m %s\n", $$1, $$2; next} {print}'

demarrer: ## Démarre l'API, la base et les e-mails
	$(DC) up -d

front: ## Démarre aussi le front Nuxt (http://localhost:3000)
	$(DC) --profile front up -d

arreter: ## Arrête tous les conteneurs
	$(DC) --profile front down

installer: ## Installe les dépendances de l'API
	$(PHP) composer install --no-interaction

migrer: ## Applique les migrations (base de dev et base de test)
	$(PHP) php bin/console doctrine:migrations:migrate --no-interaction
	$(PHP) php bin/console doctrine:migrations:migrate --no-interaction --env=test

cache: ## Vide le cache de l'API (dev et test)
	$(PHP) rm -rf var/cache/dev var/cache/test
	$(PHP) php bin/console cache:warmup

## —— Tests ——————————————————————————————————————————————
tests: ## Lance tous les tests PHPUnit
	$(PHP) php bin/phpunit

tests-unitaires: ## Lance les tests unitaires (sans base de données)
	$(PHP) php bin/phpunit tests/Unit

tests-fonctionnels: ## Lance les tests fonctionnels (requêtes HTTP sur l'API)
	$(PHP) php bin/phpunit tests/Fonctionnel

## —— Qualité ————————————————————————————————————————————
phpstan: ## Analyse statique PHPStan, niveau 6
	$(PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=1G

cs: ## Vérifie le style du code PHP (sans rien modifier)
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-corriger: ## Corrige le style du code PHP
	$(PHP) vendor/bin/php-cs-fixer fix

lint: ## Vérifie le conteneur, les YAML, Twig, composer.json et le mapping Doctrine
	$(PHP) php bin/console lint:container
	$(PHP) php bin/console lint:yaml config --parse-tags
	$(PHP) php bin/console lint:twig templates
	$(PHP) composer validate --strict
	$(PHP) php bin/console doctrine:schema:validate

audit: ## Cherche les vulnérabilités connues des dépendances (API et front)
	$(PHP) composer audit
	$(DC) exec -T front npm audit --omit=dev

qualite: lint phpstan cs audit tests ## Lance toutes les vérifications, comme la CI
