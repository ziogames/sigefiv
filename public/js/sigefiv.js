document.addEventListener('DOMContentLoaded',()=>{

    document.querySelectorAll('.formulario-eliminar').forEach(form=>{

        form.addEventListener('submit',e=>{

            e.preventDefault();

            Swal.fire({

                title:'¿Eliminar registro?',

                text:'Esta acción no se puede deshacer.',

                icon:'warning',

                showCancelButton:true,

                confirmButtonText:'Eliminar',

                cancelButtonText:'Cancelar',

                confirmButtonColor:'#d33'

            }).then(result=>{

                if(result.isConfirmed){

                    form.submit();

                }

            });

        });

    });

});