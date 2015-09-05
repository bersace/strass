========================
 Le local scout virtuel
========================

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

Tester
======

Sur système debian ::

  git clone https://github.com/bersace/strass.git
  sudo make setup
  make serve

Ensuite, suivre l'assistant à l'adresse HTTP indiquée. Voir make help pour plus
de possibilités.
