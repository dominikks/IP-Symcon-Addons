<?php

class HomeMaticJalousie extends IPSModule
{
	// IPS functions
	public function Create()
	{
		parent::Create();

		// Properties erstellen
		$this->RegisterPropertyInteger("HMUpInstance", 0);
		$this->RegisterPropertyInteger("HMDownInstance", 0);

		// Variablen erstellen
		$this->RegisterVariableBoolean("StatusVariable", "Status", "Jalousie");
		$this->EnableAction("StatusVariable");
	}

	public function RequestAction($Ident, $Value)
	{
		switch ($Ident) {
			case "StatusVariable":
				$this->SendCommand($Value);
				break;
			default:
				throw new Exception("Invalid Ident");
		}
	}

	// Function for the public API within IPS
	public function SendCommand(bool $Up)
	{
		$hminst = $this->ReadPropertyInteger($Up ? "HMUpInstance" : "HMDownInstance");
		if ($hminst) {
			HM_WriteValueFloat($hminst, "ON_TIME", 0.5);
			HM_WriteValueBoolean($hminst, "STATE", true);
		}
		$this->SetValue("StatusVariable", $Up);
	}
}
