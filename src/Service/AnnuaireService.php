<?php

namespace App\Service;

use App\Entity\DelUtilisateurInfos;
use App\Model\User;
use App\Repository\DelCommentaireRepository;
use App\Repository\DelUtilisateurInfosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AnnuaireService
{
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;
    private string $cookieName;
    private string $ssoAnnuaireUrl;
    private DelUtilisateurInfosRepository $delUserRepository;
    private DelCommentaireRepository $commentaireRepository;

    public function __construct(
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        string $ssoAnnuaireUrl,
        string $annuaireCookieName,
        DelUtilisateurInfosRepository $delUserRepository,
        DelCommentaireRepository $commentaireRepository
    ) {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->ssoAnnuaireUrl = $ssoAnnuaireUrl;
        $this->cookieName = $annuaireCookieName;
        $this->delUserRepository = $delUserRepository;
        $this->commentaireRepository = $commentaireRepository;
    }

    public function verifierJeton(string $token)
    {
        $client = new HttpBrowser();
        try {
            $client->request('GET', $this->ssoAnnuaireUrl . 'verifierjeton?token=' . $token, [
                'headers' => [
                    'Authorization' => $token,
                ],
            ]);

            $response = $client->getResponse();

            if (200 !== $response->getStatusCode()) {
                return false;
            }

            return json_decode($response->getContent(), true);
        } catch (TransportExceptionInterface $e) {
            return false;
        }
    }
    public function refreshToken(string $token, ?array $cookie = null): array
    {
//        $client = HttpClient::create();
        $client = new HttpBrowser();
        $error = null;
        $client->request('GET', $this->ssoAnnuaireUrl.'identite?token='.$token, [
//        $client->request('GET', $this->ssoAnnuaireUrl.'identite', [
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

    public function getUtilisateurAuthentifie($request): Response
    {
        $authHeader = $request->headers->get('Authorization') ?? null;

        if (!$authHeader){
            return new JsonResponse(['error' => 'Le header Authorization est obligatoire'], Response::HTTP_UNAUTHORIZED);
        }

        $jetonValide = $this->verifierJeton($authHeader);
        if (!$jetonValide) {
            return new JsonResponse(['error' => 'Le token est invalide ou a expiré'], Response::HTTP_UNAUTHORIZED);
        }

        $jetonDecode = $this->decodeToken($authHeader);
        if ($jetonDecode == null || !isset($jetonDecode['sub']) || $jetonDecode['sub'] == '') {
            return new JsonResponse(['error' => 'Erreur lors du décodage du jeton'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $jetonDecode['id']]);
        if ($user == null) {
            //1ere connexion au del
            if (!$user->getPreferences()) {
                $user->setPreferences('{"mail_notification_mes_obs":"1","mail_notification_toutes_obs":"0"}');
            }
            $user->setDatePremiereUtilisation(new \DateTime());
            $user->setDateDerniereConsultationEvenements(new \DateTime());

            $this->em->persist($user);
            $this->em->flush();
            $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $jetonDecode['id']]);
        } else {
            // Vérifier si le profil a changé
            $profilUpdated = $this->profilAChange($user, $jetonDecode);
            if ($profilUpdated) {
                $this->updateLocalProfil($user, $jetonDecode);
                $this->updateCommentsUserInfos($jetonDecode);
                $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $jetonDecode['id']]);
            }
        }
        // On transforme l'id en string sinon la deserialization ne marche pas
        $user->setIdUtilisateur((string)$user->getIdUtilisateur());

        return new Response($user->getIdUtilisateur(), Response::HTTP_OK);
    }

    private function updateLocalProfil($user, $jetonDecode): bool
    {
        $user->setNom($jetonDecode['nom']);
        $user->setPrenom($jetonDecode['prenom']);
        $user->setIntitule($jetonDecode['intitule']);
        $user->setCourriel($jetonDecode['sub']);

        $this->em->persist($user);
        $this->em->flush();

        return true;
    }

    private function updateCommentsUserInfos($jetonDecode): bool
    {
        if ($jetonDecode != null && $jetonDecode['id'] != '' && ($jetonDecode['nom'] != '' || $jetonDecode['prenom'] != '')){
            $userComments = $this->commentaireRepository->findBy(['ce_utilisateur' => $jetonDecode['id']]);

            if ($userComments){
                foreach ($userComments as $commentaire) {
                    $commentaire->setNom($jetonDecode['nom']);
                    $commentaire->setPrenom($jetonDecode['prenom']);
                    $this->em->persist($commentaire);
                }
                $this->em->flush();
            };
//            $this->em->flush();
        }
        return true;
    }

    public function isAuthorOrAdmin(DelUtilisateurInfos $user, string|int $auteur_id): bool
    {
        if ( $user->getIdUtilisateur() != $auteur_id && $user->getAdmin() < 2){
            return false;
        }

        return true;
    }

    public function isAuthorOrVerificateur(DelUtilisateurInfos $user, string|int $auteur_id): bool
    {
        if ( $user->getIdUtilisateur() != $auteur_id && $user->getAdmin() < 1){
            return false;
        }

        return true;
    }
}
