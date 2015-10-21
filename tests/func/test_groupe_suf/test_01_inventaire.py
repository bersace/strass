#
# Tests de l'inventaire du groupe : création des unités, des inscriptions.
#

import datetime
from strass.client import ClientTestCase

from fixtures import ADMIN_EMAIL, ADMIN_PASSWORD


class TestInstaller(ClientTestCase):
    # On commence par se connecter en admin. La session étant permanent dans un
    # testCase.
    def test_00_login(self):
        (
            self.client
            .get()
            .fill("#login-username", ADMIN_EMAIL)
            .fill("#login-password", ADMIN_PASSWORD)
            .submit('#aside button[type=submit]')
        )

        # S'assurer qu'on est connecté car la console affiche sa vignette. ;-)
        self.assertElementFound("#console .vignette.individu.mini")

    def test_01_creation_groupe(self):
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

    def test_02_inscrire_cg_actif(self):
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

            .fill('#inscrire-fiche-prenom', 'Christian')
            .fill('#inscrire-fiche-nom', 'Chef de Groupe')
            .click('#inscrire-fiche-sexe-h')
            # Cliquer sur le premier bouton submit
            .submit()
        )

        # On est redirigé vers les effectifs, avec la ligne du CG :-)
        self.assertElementFound('#document table.effectifs tr.chef.cg')
