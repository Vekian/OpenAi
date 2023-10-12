<?php
namespace App\Controller;


use App\Form\OpenAiType;
use App\Service\OpenAiService;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OpenAiController extends AbstractController
{
    private $openAiService;
    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    #[Route('/openai', name: 'app_openai')]
    public function index(Request $request, EntityManagerInterface $entityManager, MessageRepository $messageRepository): Response
    {
        $historique = $messageRepository->findBy([], ['createdAt' => 'DESC'], 6);
        $historiqueData = [];
        foreach($historique as $historiqueMessage) {
            array_push($historiqueData, [
                'role' => $historiqueMessage->getRole(),
                'content' => $historiqueMessage->getContent(),
            ]);
        };
        $arrayData = array_reverse($historiqueData);
        $form = $this->createForm(OpenAiType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userInput = $data['userInput'];
            $questionData =
                [
                    'role' => 'user',
                    'content' => $userInput,
                ];

            array_push($arrayData, $questionData);
            $question = new Message ($questionData);
            $entityManager->persist($question);
            $entityManager->flush();

            $messageApi = $this->openAiService->generateResponse($arrayData);
            $message = new Message($messageApi);
            $entityManager->persist($message);
            $entityManager->flush();
            $historique = $messageRepository->findBy([], ['createdAt' => 'DESC'], 6);
        }

        return $this->render('open_ai/index.html.twig', [
            'form' => $form->createView(),
            'historique' => $historique,
        ]);
    }


    
}

?>