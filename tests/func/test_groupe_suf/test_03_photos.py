import os
import unittest

from strass.client import ClientTestCase


@unittest.skipIf(os.environ.get('CIRCLECI', None), 'Dans CircleCI')
class Test(ClientTestCase):
    def test_01_envoi(self):
        (
            self.client
            # Aller sur les photos de l'unité
            .click('#aside #connexes li.photos a')
            # Aller à l'envoi de photo
            .click('#aside li.photos.envoyer a')
            # Choisir l'album du camp d'été
            .click('#document a.album-camp-d-ete-2007')

            .fill(
                '#envoyer-photo',
                self.fullpath('photo-camp-ete-2007-0.jpeg')
            )
            .fill('#envoyer-titre', 'BADEN POWELL')
            .fill(
                '#envoyer-commentaire',
                'Lord Robert Stephenson BADEN POWELL of GILWELL')
            .click('#control-envoyer-envoyer label')
            .submit()
            # Dans la vue de l'album, clique sur la photo qu'on vient d'envoyer
            .click('#document ul.vignettes a.photo-baden-powell')
        )

    def test_02_identifier(self):
        # Cliquer sur Identifier
        self.client.click('#aside .photos.identifier a')
        # S'assurer que l'unité 1 (le groupe) est bien identifiée par défaut
        self.assertAttributeEquals(
            'true', '#identifier-unites-1', 'checked')
