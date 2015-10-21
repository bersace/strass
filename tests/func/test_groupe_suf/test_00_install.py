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

    def test_03_inscrire_cg(self):
        # On inscrit un chef de groupe actif depuis l'année dernière.
        self.client.get()

        # Aller dans la page des effectifs depuis le lien latéral
        self.client.click('#aside #connexes li.unites.effectifs a')

        # S'assurer que personne n'est inscrit à ce jour.
        self.assertElementFound('#document p.empty')

        # Aller sur le formulaire d'inscription
        self.client.click('#aside #admin .unites.inscrire a')

        (
            self.client
            .click('#inscrire-inscription-individu-nouveau')
            # 1__ == Chef de groupe (premier rôle SUF, sans titre)
            .select('#inscrire-inscription-role', '1__')
            .fill(
                '#control-inscrire-inscription-debut',
                datetime.datetime.now() - datetime.timedelta(weeks=52)
            )
            .submit()

            .fill('#inscrire-fiche-prenom', 'Arnaud')
            .fill('#inscrire-fiche-nom', 'Martin')
            .click('#inscrire-fiche-sexe-h')
            # Cliquer sur le premier bouton submit
            .submit()
        )

        # On est redirigé vers les effectifs, avec la ligne du CG :-)
        self.assertElementFound('#document table.effectifs tr.chef.cg')
