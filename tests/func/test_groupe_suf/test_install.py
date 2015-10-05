import datetime
from strass.client import ClientTestCase


class TestInstaller(ClientTestCase):
    def test_00_missing_association(self):
        (
            self.client
            .get()
            .click('button[type=submit]')

            # S'assurer que le champ association est refusé.
            .find('#control-installation-site-association.invalid')
        )

    def test_01_association(self):
        (
            self.client
            .get()

            # Étape association
            .click('input#installation-site-association-suf')
            .click('button[type=submit]')

            # Étape administrateur
            .fill('input#installation-admin-prenom', 'Prénom admin')
            .fill('input#installation-admin-nom', 'Nom admin')
            .click('input#installation-admin-sexe-h')
            .fill(
                '#control-installation-admin-naissance',
                datetime.date(1990, 10, 21))
            .fill('input#installation-admin-adelec', 'admin@groupe-suf')
            .fill('input#installation-admin-motdepasse', 'secret')
            .fill('input#installation-admin-confirmation', 'secret')
            .click('button[type=submit].primary')
        )

        # L'installation est terminée.
        #
        # Page d'erreur qu'il n'y a pas d'unité :-)
        self.assertIn("Pas d'unit", self.client.page_source)

    def test_02_unite(self):
        self.client.get()
        # Pas d'unité -> page d'acceuil = 404
        self.assertElementFound('.document.strass.error.http-404')

        (
            self.client
            # suivre le lien vers le formulaire pour créer une unité.
            .click('.dialog #aide p a')

            # Fermer la fenêtre, pour basculer sur la nouvelle fenêtre
            .close()
        )

        # Pas d'unité -> pas de parent possible.
        self.assertElementFound('#fonder-parente.hidden')

        (
            self.client
            # Remplir le formulaire de création
            .fill('#fonder-nom', "St Georges")
            .submit()
        )

        # Redirigé vers la page d'accueil du nouveau groupe
        self.assertElementFound('#document.unites.index.groupe-st-georges.suf')
