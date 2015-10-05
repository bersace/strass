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
