<?php

namespace Curso\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($nombre)
    {
        return $this->render('CursoMainBundle:Default:index.html.twig', array('nombre'=>$nombre));
    }

    public function ayudaAction($tema)
    {
       return $this->render('CursoMainBundle:Default:ayuda.html.twig', array('tema'=>$tema));
        
      //  return new Response("<html><body>Esta el la ayuda sobre el tema ".$tema. "</body></html>");
    }
}
