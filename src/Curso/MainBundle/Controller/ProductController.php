<?php

namespace Curso\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Curso\MainBundle\Entity\Producto;
use Curso\MainBundle\Form\ProductoType;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends Controller
{
	// Agrega un producto
    public function addOneAction($nombre, $precio)
    {
        $producto = new Producto();        
        $producto->setNombre($nombre);
        $producto->setPrecio($precio);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($producto);
        $em->flush();        
        
    	return new Response(
    			"Id del nuevo producto: ".$producto->getId()."; el producto se ha creado OK"
    	);
    }
    
    
    // Trae todos los productos
    public function getAllAction()
    {
       $em = $this->getDoctrine()->getManager();
       $productos = $em->getRepository('CursoMainBundle:Producto')->findAll();
    /*   $res = "Productos:<br>";
       
       foreach ($productos as $producto){
       		$res .= $producto->getNombre(). ' Precio: '.$producto->getPrecio().'<br>';
       }       
       return new Response( $res );
    */
       $em = $this->getDoctrine()->getManager();
       $ciudades = $em->getRepository('CursoMainBundle:Ciudad')->findAll();

       return $this->render('CursoMainBundle:Default:productos.html.twig', array(
        "productos" =>$productos, 
        "ciudades" => $ciudades));

    }
    
    // Trae un producto por su id
    public function getByIdAction($id)
    {
    	$em = $this->getDoctrine()->getManager();
    	$producto = $em->getRepository('CursoMainBundle:Producto')->find($id);
    	    	 
    	return new Response( 
            'Producto: '.$producto->getId().', Nombre: '.$producto->getNombre().', precio: '.$producto->getPrecio().'<br>'
    	);
    }
    
    // Trae un producto por su nombre
    public function getByNombreAction($nombre)
    {
    	# otra forma de hacerlo es traer el repositorio producto de una vez 
    	$repository = $this->getDoctrine()->getRepository('CursoMainBundle:Producto');
    	 	
    	$producto = $repository->findOneByNombre($nombre);
    	 
    	return new Response(
            'Producto: '. $producto->getNombre(). ', precio: '.$producto->getPrecio().'<br>'
        );
    }
    
    // Actualiza un producto
    public function updateAction($id, $nombre, $precio)
    {
    	# traigo el manajer
    	$em = $this->getDoctrine()->getManager();
    	#cargo el producto 
    	$producto = $em->getRepository('CursoMainBundle:Producto')->find($id);
    	
    	if (!$producto){
    		#si no existe creo una exepsion
    		throw $this->createNotFoundException(
    				"No se encontró un producto con el id: $id.");
    	}
    	
    	$producto->setNombre($nombre);
    	$producto->setPrecio($precio);
    	$em->flush();
    	return new Response(
    			'Se actualizo el Producto: '. $producto->getNombre(). ', precio: '.$producto->getPrecio().'<br>'
    			);    	
    }
    
    // Borrar producto
    public function deleteAction($id)
    {
        
        $em = $this->getDoctrine()->getManager();
        $producto = $em->getRepository("CursoMainBundle:Producto")->find($id);
        
        if (!$producto){
            throw $this->createNotFoundException(
                "No se encontro un producto con el id: $id"
            );
        }
        
        $em->remove($producto);
        $em->flush();
        
        return new Response("¡Producto $id eliminado!");       
    }

    // Formulario Nuevo Producto
    public function nuevoProductoAction(Request $request)
    {   
        $producto = new Producto();

        # Creo el formulalrio y le paso los valores del producto#
        $form = $this->createForm(new ProductoType(), $producto);

        # Despues de esta linea el producto esta relleno con los datos que se le pasaron del formulario #
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($producto);
            $em->flush();

            return $this->redirect($this->generateUrl('curso_main_allProd'));
        }


        return $this->render("CursoMainBundle:Default:formulario.html.twig", array('form'=>$form->createView()));
    }


}
