<?php

namespace Curso\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;

class InfoController extends Controller
{
    
    public function nosotrosAction($nombre, $apellido)
    {
        //return $this->render('CursoMainBundle:Default:ayuda.html.twig', array('tema'=>$tema));
        
        return new Response("<html><body>Mi página de info propía; mi nombre es: ".$nombre." y mi apellido es: ".$apellido.";</body></html>");
       
    }
    
    public function pagina_estaticaAction($pagina)
    {
    	if($pagina=='quien' || $pagina=='donde')
    	{
            return $this->render('CursoMainBundle:Default:'. $pagina . '.html.twig', array());
    	}
    	else
    	{
            throw $this->createNotFoundException("<h2>Página no encontrada</h2>");
        }
        
    }
}
