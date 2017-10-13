========================
 Le local scout virtuel
========================

.. image:: https://circleci.com/gh/bersace/strass.svg?style=shield
   :target: https://circleci.com/gh/bersace/strass
   :alt: Intégration continue

Strass est un projet de moteur de site scout initié en 2007. Il s'adresse aux
unités scout des associations SUF et FSE, suivant la pédagogie unitaire.

Fonctionnalités
===============

- CV scout de chacun ;
- Effectifs des unités ;
- Calendrier d'unité, avec héritage du calendrier de l'unité parente ;
- Page de documents : fiche d'inscription, fiche sanitaire, etc. ;
- Album photos par activité ;
- Blog d'unité ;
- Citations, livre d'or, page de liens, archives, etc. ;
- Assistant d'installation.

.. image:: docs/strass-install.png
   :alt: Installateur
   :width: 90%
   :align: center


Déployer avec Docker
====================

Sur un hôte docker::

  docker-compose up

Avec dnsdock, aller à http://www.strass.docker/ . Sinon, jouer avec l'exposition
de port sur l'hôte.


Développer
==========

Sur système Debian, vous avez besoin de sqlite3 et d'un virtualenv python3 ::

  git clone https://github.com/bersace/strass.git
  cd strass/
  make setup all
  docker-compose -f docker-compose.dev.yml up

Ensuite, suivre l'assistant à l'adresse http://dev.strass.docker:8000 .
