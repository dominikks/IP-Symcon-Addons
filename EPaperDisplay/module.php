<?php

class ePaperDisplay extends IPSModule
{
  // The name of properties representing external variable IDs
  private static $Variables = [
    ["OutdoorTemperature", 0],
    ["OutdoorHumidity", 0],
    ["WindSpeed", 0],
    ["AirPressure", 0],
    ["UVIndex", 0],
    ["UVIndexDescription", ""],
    ["Ozone", 0],
    ["IsRaining", false],
    ["PowerMeter", 0],
    ["IndoorTemperature", 0],
    ["IndoorHumidity", 0],
    ["SunsetTime", 0],
    ["SunriseTime", 0],
    ["WindowsOpen", false],
    ["RainfallToday", 0],
    ["RainfallYesterday", 0],
    ["CO2", 0],
    ["SunshineHoursToday", ""],
    ["SunshineHoursYesterday", ""]
  ];

  public function Create()
  {
    parent::Create();

    $this->RegisterPropertyString("MQTTTopic", "");
    $this->RegisterPropertyInteger("RefreshSeconds", 0);
    foreach (static::$Variables as $var) {
      $this->RegisterPropertyInteger($var[0], 0);
    }

    $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

    $this->RegisterTimer("Refresh", $this->ReadPropertyInteger("RefreshSeconds"), "EPD_Update(" . $this->InstanceID . ");");
  }

  public function ApplyChanges()
  {
    parent::ApplyChanges();

    $this->SetTimerInterval("Refresh", $this->ReadPropertyInteger("RefreshSeconds") * 1000);
  }

  private function SendMQTT($payload)
  {
    $topic = $this->ReadPropertyString("MQTTTopic");
    if (!$topic) {
      return;
    }

    $resultServer = true;
    $resultClient = true;
    //MQTT Server
    $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
    $Server['PacketType'] = 3;
    $Server['QualityOfService'] = 0;
    $Server['Retain'] = true;
    $Server['Topic'] = $topic;
    $Server['Payload'] = $payload;
    $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
    $ServerJSON = json_encode($Server);

    $this->SendDebug(__FUNCTION__ . 'MQTT Server', $ServerJSON, 0);
    $resultServer = @$this->SendDataToParent($ServerJSON);

    //MQTT Client
    $Buffer['PacketType'] = 3;
    $Buffer['QualityOfService'] = 0;
    $Buffer['Retain'] = true;
    $Buffer['Topic'] = $topic;
    $Buffer['Payload'] = $payload;

    $Client['DataID'] = '{97475B04-67C3-A74D-C970-E9409B0EFA1D}';
    $Client['Buffer'] = $Buffer;
    $ClientJSON = json_encode($Client);

    $this->SendDebug(__FUNCTION__ . 'MQTT Client', $ClientJSON, 0);
    $resultClient = @$this->SendDataToParent($ClientJSON);

    if ($resultServer === false && $resultClient === false) {
      $last_error = error_get_last();
      echo $last_error['message'];
    }
  }

  public function Update()
  {
    $payload = [];

    foreach (static::$Variables as $var) {
      $varid = $this->ReadPropertyInteger($var[0]);
      $payload[$var[0]] = $varid ? GetValue($varid) : $var[1];
    }

    $payload = json_encode($payload);
    $this->SendDebug(__FUNCTION__, $payload, 0);
    $this->SendMQTT($payload);
  }
}
