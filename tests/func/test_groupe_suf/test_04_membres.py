import datetime
from strass.reuse import run_always
from strass.client import ClientTestCase

from fixtures import (
    ADMIN_EMAIL, ADMIN_PASSWORD,
    MEMBRE_EMAIL, MEMBRE_PASSWORD,
)


class Test(ClientTestCase):
    @run_always
    def test_00_logout(self):
        (
            self.client
            .submit('#aside button[type=submit]')
        )
        self.assertElementNotFound('#aside #logout')

    def test_01_inscrire(self):
        (
            self.client
            .click('#aside li.inscription a')

            .fill('input#inscription-fiche-prenom', 'Maxime')
            .fill('input#inscription-fiche-nom', 'Membre')
            .click('#control-inscription-fiche-sexe label.h')
            .fill(
                '#control-inscription-fiche-naissance',
                datetime.datetime(1982, 4, 23))
            .submit()

            .fill('input#inscription-compte-adelec', MEMBRE_EMAIL)
            .fill('input#inscription-compte-motdepasse', MEMBRE_PASSWORD)
            .fill('input#inscription-compte-confirmer', MEMBRE_PASSWORD)
            .fill(
                'textarea#inscription-compte-presentation',
                u"Présentation du membre à son inscription")

            .submit()
        )

    @run_always
    def test_99_reconnexion(self):
        (
            self.client
            .fill('input#login-username', ADMIN_EMAIL)
            .fill('input#login-password', ADMIN_PASSWORD)
            .click('#aside button[type=submit]')
        )
        self.assertElementFound('#aside #logout')
