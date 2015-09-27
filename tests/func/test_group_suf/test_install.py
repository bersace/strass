from strass.client import ClientTestCase

class TestInstaller(ClientTestCase):
    def test_00_missing_association(self):
        (
            self.client
            .get()
            .click('button[type=submit]')

            # S'assurer que le champ mouvement est refusé.
            .find('#control-installation-site-mouvement.invalid')
        )
