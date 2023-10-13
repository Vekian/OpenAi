<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MessageRepository;
use App\Entity\Message;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class OpenAiService
{

    public function __construct(private EntityManagerInterface $entityManager, private MessageRepository $messageRepository, private ParameterBagInterface $parameterBag) {

    }

    public function generateResponse($form)
    {
        $data = $form->getData();
        $userInput = $data['userInput'];
        $questionData =
            [
                'role' => 'user',
                'content' => $userInput,
            ];

        $this->persistData($questionData);
        $historique = $this->getHistorique("old");

        // Get your API key from your environment
        $yourApiKey = $this->parameterBag->get('OPENAI_API_KEY');
        $httpClient = HttpClient::create();

        // Create a completion request
        try {
            $response = $httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $yourApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => "gpt-3.5-turbo",
                'messages' => $historique,
            ],
        ]);
        } catch (\Exception $e) {
            return 'Exception: ' . $e->getMessage() . "\n";
        }
        

        $responseData = $response->toArray();
        // Get the generated response text from the completion

        $messageApi = $responseData['choices'][0]['message'];
        $this->persistData($messageApi);
        return new JsonResponse($this->getHistorique('new'));
    }

    public function persistData($data) {
        $message = new Message ($data);
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    public function getHistorique($state) {
        $newHistorique = $this->messageRepository->findBy([], ['createdAt' => 'DESC'], 6);
        $array= [];
        foreach($newHistorique as $message){
            if ($state === "new") {
            $arrayMessage = [
            'role' => $message->getRole(),
            'content' => $message->getContent(),
            'createdAt' => $message->getCreatedAt()->format('d-m-Y H:i:s')];
            array_push($array, $arrayMessage);
            }
            else if ($state === "old") {
                $arrayMessage = [
                'role' => $message->getRole(),
                'content' => $message->getContent()];
                array_push($array, $arrayMessage);
            }
        }
        return array_reverse($array);
    }

}

?>