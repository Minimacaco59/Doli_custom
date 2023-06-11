<?php
use PHPUnit\Framework\TestCase;

class QrcodeApiTest extends TestCase
{
    public function testPost()
    {
        // Préparation des données de la requête
        $request_data = [
            'name' => 'Toto',
            'api_key' => 'dolapikey',
            'email' => 'toto@epsi.fr'
        ];

        // Mock de la fonction sendEmail pour éviter d'envoyer réellement un e-mail
        $this->getMockBuilder('QrcodeApi')
            ->setMethods(['sendEmail'])
            ->getMock();

        // Instance de la classe à tester
        $qrcodeApi = new QrcodeApi();

        // Exécution de la méthode à tester
        $result = $qrcodeApi->post($request_data);

        // Assertions sur le résultat
        $this->assertNotNull($result);
        $this->assertIsString($result);
    }

    // Autres méthodes de test si nécessaire...
}
