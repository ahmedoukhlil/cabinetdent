# Variables d'environnement pour l'optimisation des performances

## Configuration recommandée

Copiez ces variables dans votre fichier `.env` pour activer les optimisations de performance :

```bash
# Cache des requêtes
QUERY_CACHE_ENABLED=true
QUERY_CACHE_TTL=300
QUERY_CACHE_MAX=1000

# Optimisations de base de données
DB_QUERY_LOG=false
DB_SLOW_QUERY_THRESHOLD=100
DB_CONNECTION_POOLING=false
DB_STATEMENT_TIMEOUT=30

# Livewire optimisations
LIVEWIRE_CACHE_COMPONENTS=true
LIVEWIRE_CACHE_QUERIES=true
LIVEWIRE_LAZY_LOAD=true
LIVEWIRE_DEBOUNCE=150

# Cache des vues
VIEWS_CACHE_COMPILED=true
VIEWS_CACHE_RENDERED=false
VIEWS_MINIFY_HTML=false

# Cache des modèles
MODELS_CACHE_RELATIONS=true
MODELS_CACHE_QUERIES=true
MODELS_CACHE_TTL=3600

# Optimisations des assets
ASSETS_MINIFY_CSS=true
ASSETS_MINIFY_JS=true
ASSETS_COMBINE=true
ASSETS_VERSION=true

# Monitoring des performances
PERFORMANCE_MONITORING=true
LOG_SLOW_QUERIES=true
LOG_MEMORY_USAGE=false
LOG_EXECUTION_TIME=true
SLOW_QUERY_THRESHOLD=100
MEMORY_LIMIT_THRESHOLD=128
EXECUTION_TIME_THRESHOLD=1000

# Configuration MySQL optimisée
MYSQL_ATTR_USE_BUFFERED_QUERY=true
MYSQL_ATTR_PERSISTENT=false
MYSQL_ATTR_INIT_COMMAND="SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'"

# Cache driver (recommandé: redis en production)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

## Variables par environnement

### Développement
```bash
APP_DEBUG=true
DB_QUERY_LOG=true
PERFORMANCE_MONITORING=true
LOG_SLOW_QUERIES=true
```

### Production
```bash
APP_DEBUG=false
DB_QUERY_LOG=false
PERFORMANCE_MONITORING=true
LOG_SLOW_QUERIES=false
CACHE_DRIVER=redis
```

### Test
```bash
APP_DEBUG=true
CACHE_DRIVER=array
DB_QUERY_LOG=true
PERFORMANCE_MONITORING=false
```

## Activation des optimisations

Après avoir ajouté ces variables :

1. **Vider le cache de configuration** :
```bash
php artisan config:clear
```

2. **Vider le cache de l'application** :
```bash
php artisan cache:clear
```

3. **Redémarrer l'application** si nécessaire

## Vérification

Pour vérifier que les optimisations sont actives :

```bash
php artisan config:show performance
php artisan test:performance
```
