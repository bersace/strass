import os
import unittest
from selenium import webdriver

class TestInstaller(unittest.TestCase):
    def setUp(self):
        self.driver = webdriver.PhantomJS()
        self.driver.set_window_size(1120, 550)

    def tearDown(self):
        self.driver.quit()

    def test_00_missing_association(self):
        self.driver.get(os.environ['STRASS_TEST_SERVER'])
        self.driver.find_element_by_css_selector('button[type=submit]').click()
        # S'assurer que le champ mouvement est refusé
        self.driver.find_element_by_css_selector('#control-installation-site-mouvement.invalid')
