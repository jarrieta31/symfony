<?php

namespace EMM\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request; # Es para poder recibir la peticion del formulario para la funcion createAction
use Symfony\Component\HttpFoundation\Response; #permite crear respuestas desde el controlador sin usar platillas, y para ajax
use Symfony\Component\Security\Http\RememberMe\ResponseListener;
use EMM\UserBundle\Entity\User; // para acceder a los metodos del objeto User lo importo primero
use EMM\UserBundle\Form\UserType;
use Symfony\Component\Validator\Constraints as Assert; # componente para validar campos
use Symfony\Component\Form\FormError; # componente para trabajar con los errores en los formularios

class UserController extends Controller
{   
    public function indexAction(Request $request)# para dql se debe importar el objeto Request
    {
        
        $em = $this->getDoctrine()->getManager();

        //comentada para utilizar dql     $users = $em->getRepository('EMMUserBundle:User')->findall();

        /* Todo este comentario se utilizó para traer los usuarios sin utilizar plantillas
        $res = 'Lista de usuarios: <br/>';

        foreach ($users as $user) {

        	$res.= 'Usuario: ' . $user->getUsername() . ' - Email: '. $user->getEmail() . '<br/>';
        }

        return new Response($res);
        } 

        */

        $dql= "SELECT u FROM EMMUserBundle:User u ORDER BY u.id DESC";

        $users = $em->createQuery($dql);

        $paginator = $this->get('knp_paginator'); #creo el objeto paginator

        $pagination = $paginator->paginate(
            $users, $request->query->getInt('page', 1), # el 1 indica en que paigna se muestra la paginación
            6  # numero maximo a mostrar por pagina
        ); 

        $deleteFormAjax = $this->createCustomForm(':USER_ID', 'DELETE', 'emm_user_delete' ); # llamo al metodo

	    return $this->render('EMMUserBundle:User:index.html.twig', array(
                'pagination' => $pagination, 
                'delete_form_ajax' => $deleteFormAjax->createView()
                ));
	}


	private function createCreateForm(User $entity)
	{
		$form = $this->createForm(new UserType(), $entity, array(
				'action' =>$this->generateUrl('emm_user_create'), 
				'method' => 'POST'
			));

		return $form;
	}


    public function createAction(Request $request)
    {   
        $user = new User();

        $form = $this->createCreateForm($user); # paso el objeto user al formulario para que sepa lo que tiene que recibir

        $form->handleRequest($request); # obtengo el request con los datos ingresados en el formulario

        # verifico si el formulario es valido
        if ($form->isValid()) 
        {   
            $password = $form->get('password')->getData(); # obtengo el password que se ingreso en el formulario

            $passwordConstraint = new Assert\NotBlank(); # Creo la regla de validacion 

            $errorList = $this->get('validator')->validate($password, $passwordConstraint); # obtengo los errores

            if( count($errorList) == 0) #si no hay errores
            {
                $encoder = $this->container->get('security.password_encoder'); # este objeto va a codificar el password

                $encoded = $encoder->encodePassword($user, $password); # codifico el password y lo guardo en esta variable

                $user->setPassword($encoded); # finalmente con el metodo setPassword seteamos la campo password para enviar a la bd

                $em = $this->getDoctrine()->getManager();# obtengo el manejador de doctrine

                $em->persist($user); # paso el objeto a guardar

                $em->flush(); # confirmo la trasacción

                $successMessage = $this->get('translator')->trans('The user has been created.'); 

                $this->addFlash('mensaje', $successMessage);

                return $this->redirectToRoute('emm_user_index');                
            }
            else # si la contraseña está vácia
            {
                $errorMessage = new FormError($errorList[0]->getMessage()); #obtengo el error y lo guardo en una variable

                $form->get('password')->addError($errorMessage); # mando el error al formulario
            }

        }

        # en caso de fallar la validacion voy al formulario nuevamente
        return $this->render('EMMUserBundle:User:add.html.twig', array('form'=>$form->createView()));

    }


	public function addAction()
	{
		$user = new User();

		$form = $this->createCreateForm($user);

		return $this->render('EMMUserBundle:User:add.html.twig', array('form'=>$form->createView()));
	}


    public function editAction($id) # muestra el formulario edit creado con la funcion createEditForm
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('EMMUserBundle:User')->find($id);

        if(!$user)
        {
            $messageExeption = $this->get('translator')->trans('User not found.');

            throw $this->createNotFoundException($messageExeption);       
        }  

        $form = $this->createEditForm($user);

        return $this->render('EMMUserBundle:User:edit.html.twig', array('user'=>$user, 'form'=>$form->createView()));      
    }


    private function createEditForm(User $entity) # funcion para crear el formulario edit user
    {
        $form = $this->createForm(new UserType(), $entity, 
            array('action'=>$this->generateUrl('emm_user_update', array('id'=>$entity->getId())),
                'method'=>'PUT'));

        return $form;
    }


    public function updateAction($id, Request $request) # funcion disparada por el boton actualizar usuario en el form edit
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('EMMUserBundle:User')->find($id);

        if(!$user)
        {
            $messageExeption = $this->get('translator')->trans('User not found.');

            throw $this->createNotFoundException($messageExeption);       
        }

        $form = $this->createEditForm($user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {   
            $password = $form->get('password')->getData(); #recupero la contraseña ingresada en el formulario

            if(!empty($password)) # verifico si el usuario coloco un password nuevo
            {
                $encoder = $this->container->get('security.password_encoder'); #creo un objeto para codicar el password

                $encoded = $encoder->encodePassword($user, $password); # utilizo el objeto creado y paso la entidad y el campo

                $user->setPassword($encoded);# Seteo el campo password con la contraseña encriptada
            }
            else
            {
                $recoverPass = $this->recoverPass($id); # llamo la funcion privada recoverPass()

                /*print_r($recoverPass);
                exit();  */

                $user->setPassword($recoverPass[0]['password']);
            }


            if ($form->get('role')->getData()=='ROLE_ADMIN') # si el usuario es administrador no se puede deshabilitar
            {                
                $user->setIsActive(1);
            }

            $em->flush();

            $successMessage = $this->get('translator')->trans('The user has been modifiend.');

            $this->addFlash('mensaje', $successMessage);

            return $this->redirectToRoute('emm_user_edit', array('id'=>$user->getId()));
        }

        # si hay un error muestra otra vez el formulario edit
        return $this->render('EMMUserBundle:User:edit.html.twig', array('user'=>$user, 'form'=>$form->createView()));

    }


    private function recoverPass($id)
    {
        $em = $this->getDoctrine()->getManager();

        $query = $em->createQuery(

            'SELECT u.password 
            FROM EMMUserBundle:User u
            WHERE u.id = :id'

        )->setParameter('id', $id); # esta es la forma de usar los marcadores en doctrine

        $currentPass = $query->getResult(); # cargo el resultado de la consulta en una variable

        return $currentPass;
    }


    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('EMMUserBundle:User');

        $user = $repository->find($id);

        if(!$user) # verifica si el usuario existe
        {
            $messageExeption = $this->get('translator')->trans('User not found.');

            throw $this->createNotFoundException($messageExeption);       
        }

        $deleteForm = $this->createCustomForm($user->getRole(), 'DELETE', 'emm_user_delete');

        return $this->render('EMMUserBundle:User:view.html.twig', array(
            'user'=>$user, 
            'delete_form' => $deleteForm->createView()
        ));

    }

    
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('EMMUserBundle:User')->find($id);

        if(!$user) # verifica si el usuario existe
        {
            $messageExeption = $this->get('translator')->trans('User not found.');

            throw $this->createNotFoundException($messageExeption);       
        }

        $allUser = $em->getRepository('EMMUserBundle:User')->findAll(); #obtengo todos los usuarios

        $countUsers = count($allUser); # Nuemero de usuarios

       // $form = $this->createDeleteForm($user); # cargo el fomulario 

        $form = $this->createCustomForm($user->getId(), 'DELETE', 'emm_user_delete' ); # metodo para crear el formulario

        $form->handleRequest($request); # proceso el formulario enviandole el objeto request

        if($form->isSubmitted() && $form->isValid()) # verifico si el formulario es valido y si se envio correctamente
        {
            
            if( $request->isXmlHttpRequest() ) # Verifica si la peticion es por ajax
            {
                $res = $this->deleteUser($user->getRole(), $em, $user); # res guarda el arreglo retornado por deleteUser

                # respueta json para el javascript
                return new Response( 
                    json_encode(array('removed'=>$res['removed'], 
                                        'message'=>$res['message'], 
                                        'countUsers'=>$countUsers)), # datos enviados
                        200,                                             # respuesta ok
                        array('Content-Type'=>'application/json')      # tipo de datos a enviar
                );
            }

            $res = $this->deleteUser($user->getRole(), $em, $user);        

            $this->addFlash($res['alert'], $res['message']);

            return $this->redirectToRoute('emm_user_index');
        }

    }


    private function deleteUser($role, $em, $user)
    {
        if($role == 'ROLE_USER' )
        {
            $em->remove($user);

            $em->flush();

            $message = $this->get('translator')->trans('The user has been deleted.');

            $removed = 1; // respuesta en 1 para cuando el usurio se elimina correctamente

            $alert = 'mensaje'; // respuesta para cuando el usuario no es eliminado
        }
        elseif ($role == 'ROLE_ADMIN')
        {
            $message = $this->get('translator')->trans('The user could not be deleted.');

            $removed = 0; // respuesta en 0 para cuando el usurio no se elimina correctamente

            $alert = 'error'; // respuesta para cuando el usuario no es eliminado
        }

        return array('removed'=>$removed, 'message'=>$message, 'alert'=>$alert);
    }

    private function createCustomForm($id, $method, $route)
    {
        return $this->createFormBuilder()# crea el formulario
            ->setAction($this->generateUrl($route, array('id' => $id))) # pasa la url y el id
            ->setMethod($method) # pasa el meto a utilizar
            ->getForm(); # trae el formulario
    }




}
