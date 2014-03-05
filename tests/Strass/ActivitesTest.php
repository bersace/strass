<?php

require_once 'Strass/Activites.php';

class ActivitesTest extends PHPUnit_Framework_TestCase
{
  function intituleFixtures()
  {
    $troupe = new TypeUnite;
    $troupe->accr_reunion = 'Réunion';
    $troupe->nom_reunion = 'Réunion';
    $troupe->accr_sortie = 'Sortie';
    $troupe->nom_sortie = 'Sortie de troupe';
    $troupe->accr_we = 'WET';
    $troupe->nom_we = 'Weekend de troupe';
    $troupe->accr_camp = 'Camp';
    $troupe->nom_camp = 'Camp';

    return array(array($troupe,
		       '2014-03-01 19:30:00', '2014-03-01 22:00:00', null, null,
		       'Réunion 1 mars 2014', 'Réunion', 'Réunion'),
		 array($troupe,
		       '2013-10-05 14:30:00', '2013-10-06 17:00:00', 'Rentrée', null,
		       'Rentrée 2013', 'Rentrée', 'Rentrée'),
		 array($troupe,
		       '2014-04-06 10:30:00', '2014-04-06 16:00:00', null, null,
		       'Sortie de troupe avril 2014', 'Sortie de troupe', 'Sortie'),
		 array($troupe,
		       '2014-04-05 15:30:00', '2014-04-06 16:00:00', null, null,
		       'Weekend de troupe avril 2014', 'Weekend de troupe', 'WET'),
		 array($troupe,
		       '2014-03-01 14:30:00', '2014-03-02 17:00:00', null, 'Lardy',
		       'Weekend de troupe Lardy mars 2014', 'Weekend de troupe Lardy', 'WET Lardy'),
		 array($troupe,
		       '2014-04-21 8:30:00', '2014-04-27 16:00:00', null, 'Confrécourt',
		       'Camp de Pâques Confrécourt 2014', 'Camp de Pâques Confrécourt', 'Camp Confrécourt'),
		 array($troupe,
		       '2014-07-07 8:30:00', '2014-07-29 16:00:00', null, 'Confrécourt',
		       'Camp Confrécourt 2014', 'Camp Confrécourt', 'Camp Confrécourt'),
		 );
  }

  /**
   * @dataProvider intituleFixtures
   */
  function testIntutiles($t, $debut, $fin, $intitule, $lieu, $complet, $calendrier, $court)
  {
    $a = new Activite;
    $a->debut = $debut;
    $a->fin = $fin;
    $a->intitule = $intitule;
    $a->lieu = $lieu;

    $this->assertEquals($complet, $t->getIntituleCompletActivite($a));
    $this->assertEquals($calendrier, $t->getIntituleActivite($a));
    $this->assertEquals($court, $t->getIntituleCourtActivite($a));
  }
}
