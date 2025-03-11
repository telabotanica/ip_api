<?php

namespace App\Service;

use App\Model\User;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class AnnuaireService
{
    private string $cookieName;
    private string $ssoAnnuaireUrl;

    public function __construct(
        string $ssoAnnuaireUrl,
        string $annuaireCookieName,
    ) {
        $this->ssoAnnuaireUrl = $ssoAnnuaireUrl;
        $this->cookieName = $annuaireCookieName;
    }

    public function verifierJeton(string $token)
    {
        $client = new HttpBrowser();
        $client->request('GET', $this->ssoAnnuaireUrl.'verifierjeton?token=' . $token, [
            'headers' => [
                'Authorization' => $token,
            ],
        ]);
        $response = $client->getResponse();

        if (200 !== $response->getStatusCode()) {
            return false;
        }

        return json_decode($response->getContent(), true);
    }
    public function refreshToken(string $token, ?array $cookie = null): array
    {
//        $client = HttpClient::create();
        $client = new HttpBrowser();
        $error = null;
//        $client->request('GET', $this->ssoAnnuaireUrl.'identite?token='.$token);
        $client->request('GET', $this->ssoAnnuaireUrl.'identite', [
            'headers' => [
                'Authorization' => $token,
            ],
        ]);

        $response = $client->getResponse();

        if (200 !== $response->getStatusCode()) {
            $error = 'error';
            if (400 === $response->getStatusCode()) {
                $error = 'Erreur lors du rafraichissement du token. Veuillez vous reconnecter';
            }
        }

        return [
            'token' => json_decode($response->getContent(), true)['token'] ?? null,
            'error' => $error
        ];
    }

    /**
     * @return User
     */
//    public function getUser(string $token, ?array $cookie = null)
//    {
//        $tokenInfos = $this->decodeToken($token);
//
//        $user = new User();
//        $user->setEmail($tokenInfos['sub'])
//            ->setName($tokenInfos['intitule'])
//            ->setAvatar(($tokenInfos['avatar'] ?? ''))
//            ->setId($tokenInfos['id'])
//            ->setPermissions($tokenInfos['permissions'][0]);
//
//        return $user;
//    }

    /**
     * Decodes a formerly validated JWT token and returns the data it contains
     * (payload / claims)
     */
    public function decodeToken($token) {
        $parts = explode('.', $token);
        $payload = $parts[1];
        $payload = $this->urlsafeB64Decode($payload);
        $payload = json_decode($payload, true);

        return $payload;
    }

    /**
     * Method compatible with "urlsafe" base64 encoding used by JWT lib
     */
    public function urlsafeB64Decode($input) {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function getCookieName(): string
    {
        return $this->cookieName;
    }

    public function profilAChange($user, $jetonDecode){
        $aChange = false;
        if ($jetonDecode != null) {
            $aChange = ($jetonDecode['nom'] != $user->getNom())
                || ($jetonDecode['intitule'] != $user->getIntitule())
                || ($jetonDecode['prenom'] != $user->getPrenom());
        }

        return $aChange;
    }

    public function demarrerSession() {
        if (session_id() == '') {
            session_start();
        }
    }

    public function getUtilisateurAnonyme() {
        $this->demarrerSession();
        return array(
            'connecte' => false,
            'id_utilisateur' => session_id(),
            'courriel' => '',
            'nom' => '',
            'prenom' => '',
            'admin' => '0',
            'session_id' => session_id()
        );
    }
}
