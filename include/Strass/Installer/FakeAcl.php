<?php

class Strass_Installer_FakeAcl
{
  function has() { return true; }
  function add() {}
  function addRole() {}
  function allow() {}
  function deny() {}
  function isAllowed() { return true; }
}
