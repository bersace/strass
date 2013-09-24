<?php
/* Vu les prise de têtes pour assurer une compatibilité avec certaines version
d'IE, on propose au utilisateurs d'IE d'utiliser une alternative */
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE
    && strpos($_SERVER['HTTP_ACCEPT'], 'xhtml') === FALSE) {
    $s = $this->addons->addSection('navigateurs', 'Conseil de navigation');
    $s->addText(
    "Pour une //meilleure// visite, utilisez une ".
    "[http://openweb.eu.org/articles/navigateurs_alternatifs/ alternative décente] ".
    "à votre navigateur !");
}
