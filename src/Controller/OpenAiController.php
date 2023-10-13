<?php
namespace App\Controller;


use App\Form\OpenAiType;
use App\Service\OpenAiService;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MessageRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    #[Route('/', name: 'app_openai')]
    public function index(Request $request, EntityManagerInterface $entityManager, MessageRepository $messageRepository): Response
    {
        $historique = $messageRepository->findBy([], ['createdAt' => 'DESC'], 6);
        
        $form = $this->createForm(OpenAiType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return ($this->openAiService->generateResponse($form));
        }

        return $this->render('open_ai/index.html.twig', [
            'form' => $form->createView(),
            'historique' => $historique,
        ]);
    }


    
}

?>