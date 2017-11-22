# Test fonctionnels

Pour lancer les tests fonctionnels:

``` console
$ docker-compose up -d
$ docker-compose exec strass /bin/bash
root@0f61551afbe6:/strass# TESTCASE=test_groupe_suf BREAKPOINT=1 tests/func/test-runner.sh
...
```

Un test case est un scénario type d'un site strass: de l'installation à
l'archivage. La date est fixée au 9 août 2007, centenaire de Brownsea, et le
temps est accéléré cent fois.

On peut naviguer dans l'instance en cours de test à l'adresse
http://test.strass.docker:9000/ . Les mots de passes sont définis dans les
fichiers `fixtures.py`.

Les test sont écrit avec `unittest` de base. Quelques assistances sont
implémentée dans `site-packages/`.

Pour aller plus loin, faut lire le code, regarder également comment ils sont
exécutés sur CircleCI.
