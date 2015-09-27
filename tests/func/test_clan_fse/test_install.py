# -*- coding: utf-8 -*-

import os
import unittest
from selenium import webdriver

class TestInstaller(unittest.TestCase):
    def setUp(self):
        self.driver = webdriver.PhantomJS()
        self.driver.set_window_size(1120, 550)

    def tearDown(self):
        self.driver.quit()

    def test_00_install(self):
        self.driver.get(os.environ['STRASS_TEST_SERVER'])
        self.driver.find_element_by_css_selector('input#installation-site-mouvement-fse').click()
        self.driver.find_element_by_css_selector('button[type=submit]').click()

        (self.driver
         .find_element_by_css_selector('input#installation-admin-prenom')
         .send_keys(u'Prénom admin'))

        (self.driver
         .find_element_by_css_selector('input#installation-admin-nom')
         .send_keys(u'Nom admin'))

        self.driver.find_element_by_css_selector('input#installation-admin-sexe-h').click()

        (self.driver
         .find_element_by_css_selector('#control-installation-admin-naissance input.day')
         .send_keys(u'21'))
        (self.driver
         .find_element_by_css_selector('#control-installation-admin-naissance input.month')
         .send_keys(u'10'))
        (self.driver
         .find_element_by_css_selector('#control-installation-admin-naissance input.year')
         .send_keys(u'1990'))

        (self.driver
         .find_element_by_css_selector('input#installation-admin-adelec')
         .send_keys(u'admin@groupe-suf'))

        (self.driver
         .find_element_by_css_selector('input#installation-admin-motdepasse')
         .send_keys(u'secret'))

        (self.driver
         .find_element_by_css_selector('input#installation-admin-confirmation')
         .send_keys(u'secret'))

        self.driver.find_element_by_css_selector('button[type=submit].primary').click()

        # Page d'erreur qu'il n'y a pas d'unité :-)
        self.assertIn("Pas d'unit", self.driver.page_source)
