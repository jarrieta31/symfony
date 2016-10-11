$(document).ready(function () {
	
	$('.btn-delete').click(function(e){
		
		e.preventDefault(); // previene que se recarge la pagina al pulsar el boton eliminar

		var row = $(this).parents('tr'); // obtengo la fila

		var id = row.data('id'); // de la fila obtengo el id del usuario

		//alert(id);

		var form = $('#form-delete'); // Obtengo el formulario form-delete

		var url = form.attr('action').replace(':USER_ID', id); // reemplazo USER_ID por el id

		var data = form.serialize(); // serializo los datos para ser enviados

		// alert(data); 

		bootbox.confirm(message, function(res){

			if(res == true) // si el usuario confirma la eliminacion
			{
				$.post(url, data, function(result) // funcion que procesa la respuesta del servidor
				{
					if (result.removed == 1) // si el usuario se elemino correctamente
					{
						row.fadeOut(); // borro la fila correspondiente

						$('#message').removeClass('hidden'); // muestro el div del mensaje

						$('#user-message').text(result.message); // escribo el mensaje recibido del servidor

						var totalUsers = $('#total').text(); // obtengo el total de registros de pagiantion en la vista

						if ( $.isNumeric(totalUsers) ) // verifico que totalUsers sea un numero
						{
							$('#total').text(totalUsers - 1); // imprimo el valor menos 1
						}
						else
						{
							$('#total').text(result.totalUsers); // si lo anterior no funciono imprimo el valor recibido
						}
					}
					else // Si el usuario no fue eliminado
					{	
						
						alert(result.message);

						$('#message-danger').removeClass('hidden'); // muestra el div de error danger

						$('#user-message-danger').text(result.message);
					}
				
				}).fail(function(){
					
					alert('ERROR'); 
					row.show();
				});
			}
		} );

	});
})