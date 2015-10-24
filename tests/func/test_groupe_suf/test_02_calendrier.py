#
# Tests de l'inventaire du groupe : création des unités, des inscriptions.
#

from datetime import date
from strass.client import ClientTestCase

from fixtures import ADMIN_EMAIL, ADMIN_PASSWORD


class Test(ClientTestCase):
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

    def test_01_prevoir(self):
        # Aller sur le calendrier
        self.client.click('#aside #connexes li.activites a')
        self.assertElementFound('#document p.empty')
        # Prévoir une activitée
        self.client.click('#aside li.activites.prevoir a')
        self.assertElementFound('#document p.empty')

    def test_02_rentree(self):
        (
            self.client
            .fill('#control-prevoir-debut', date(2006, 9, 9))
            .fill('#control-prevoir-fin', date(2006, 9, 10))
            .fill('#prevoir-intitule', "Rentrée")
            .submit()
        )

        # Pas d'erreur
        self.assertElementNotFound('#document form .control.invalid')

        # On est redirigé vers le formulaire d'envoi
        self.assertElementFound('#document.prevoir')

    def test_03_camp(self):
        # On a un aperçu du calendrier (avec la rentrée)
        self.assertElementFound('#document #calendrier table')

        (
            self.client
            .fill('#control-prevoir-debut', date(2007, 8, 1))
            .fill('#control-prevoir-fin', date(2007, 8, 8))
            # Pas d'autres activités à prévoir
            .click('#prevoir-prevoir')
            .submit()
        )

        # On est redirigé vers la dernière chaîne
        self.assertElementFound('#document.consulter')
