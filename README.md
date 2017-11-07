# Strass, le local scout virtuel

[![Intégration continue](https://circleci.com/gh/bersace/strass.svg?style=shield)](https://circleci.com/gh/bersace/strass)
[![Image docker](https://img.shields.io/docker/build/bersace/strass.svg)](https://hub.docker.com/r/bersace/strass/)


Strass est un projet de moteur de site scout initié en 2007. Il s'adresse aux
unités scout des associations SUF et FSE, suivant la pédagogie unitaire.


## Fonctionnalités

- CV scout de chacun ;
- Effectifs des unités ;
- Calendrier d'unité, avec héritage du calendrier de l'unité parente ;
- Page de documents : fiche d'inscription, fiche sanitaire, etc. ;
- Album photos par activité ;
- Blog d'unité ;
- Citations, livre d'or, page de liens, archives, etc. ;
- Assistant d'installation.

![Installateur](https://github.com/bersace/strass/raw/master/docs/strass-install.png)


## Déployer avec Docker

Un image Docker est disponible avec serveur FCGI :

    docker run --rm bersace/strass devserver


## Développer

Sur système Debian, vous avez besoin de Docker :

    git clone https://github.com/bersace/strass.git
    cd strass/
    docker-compose up -d

Ensuite, suivre l'assistant à l'adresse http://dev.strass.docker:8000 .
