<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Node\Stmt\TryCatch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/", name="app_user")
     */
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            
        ]);
    }
    /**
     * @Route("/inscription", name="inscription")
     * 
     */
    public function inscription(ManagerRegistry $doctrine, Request $req, UserPasswordEncoderInterface $encoder, SluggerInterface $slugger){
        $user= new User();
        $form = $this->createForm(UserType::class,$user);

        $form->handleRequest($req);
        if($form->isSubmitted()&& $form->isValid()){
            $hash = $encoder->encodePassword($user,$user->getPassword());
            $user->setPassword($hash);
            //ajout profilPic
            /** @var UploadedFile $img */
            $img= $form->get('avatar')->getData();
            if($img){
                $originalName= pathinfo($img->getClientOriginalName(),PATHINFO_FILENAME);
                $safeName=$slugger->slug($originalName);
                $newName=$safeName."-".uniqid().".".$img->guessExtension();

                try {
                    $img->move($this->getParameter('avatar',$newName));
                } catch (FileException $th) {
                    $th->getMessage();
                }
                $user->setAvatar($newName);
            }
            $om=$doctrine->getManager();
            $om->persist($user);
            $om->flush();
            return $this->redirectToRoute("app_user");
        }

        return $this->render("user/add.html.twig",[
            "formulaire"=> $form->createView()
        ]);
    }
}
