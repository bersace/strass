import datetime
from strass.reuse import run_always
from strass.client import ClientTestCase

from fixtures import ADMIN_EMAIL, ADMIN_PASSWORD


class TestInstaller(ClientTestCase):
    def test_00_missing_association(self):
        (
            self.client.get()
            .click('button[type=submit]')
        )

        # S'assurer que le champ association est refusé.
        self.assertElementFound(
            '#control-installation-site-association.invalid')

    def test_01_association_suf(self):
        (
            self.client
            .click('input#installation-site-association-suf')
            .click('button[type=submit]')
        )

        # On est bien passé à la page administrateur
        self.assertElementFound('#installation-admin-prenom')

    def test_02_admin_mauvaise_confirmation(self):
        (
            self.client
            .fill('input#installation-admin-prenom', 'Isidore')
            .fill('input#installation-admin-nom', 'Installeur')
            .click('input#installation-admin-sexe-h')
            .fill(
                '#control-installation-admin-naissance',
                datetime.date(1990, 10, 21))
            .fill('input#installation-admin-adelec', ADMIN_EMAIL)
            .fill('input#installation-admin-motdepasse', ADMIN_PASSWORD)
            .fill('input#installation-admin-confirmation',
                  ADMIN_PASSWORD + 'POUET')
            .click('button[type=submit].primary')
        )

        # S'assurer que le champ confirmation est refusé.
        self.assertElementFound(
            '#control-installation-admin-confirmation.invalid')

    def test_03_admin_succes(self):
        (
            self.client
            .fill('input#installation-admin-motdepasse', ADMIN_PASSWORD)
            .fill('input#installation-admin-confirmation', ADMIN_PASSWORD)
            .click('button[type=submit].primary')
        )

    def test_04_succes(self):
        self.client.get()

        # L'installation est terminée.
        #
        # Page d'erreur qu'il n'y a pas d'unité :-)
        self.assertElementFound('#document.http-404')
        self.assertIn("Pas d'unit", self.client.page_source)

    def test_05_deconnexion(self):
        (
            self.client
            .submit('#aside button[type=submit]')
        )
        self.assertElementNotFound('#aside #logout')

    @run_always
    def test_06_reconnexion(self):
        (
            self.client
            .get()
            .fill('input#login-username', ADMIN_EMAIL)
            .fill('input#login-password', ADMIN_PASSWORD)
            .click('#aside button[type=submit]')
        )
        self.assertElementFound('#aside #logout')
