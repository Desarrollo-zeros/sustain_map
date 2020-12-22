

function uploadFile(event){
	event.preventDefault();
	var formData = new FormData(document.getElementById("frm_ex"));
	$.ajax({
		beforeSend: function () {
             $("#mdl_upload").modal('show');
          },
          url: "../app_modules/maps/API/maps/upl_excel",
          type: "POST",
          data: formData,
          timeout: 30000,
          dataType: 'json',
          cache: false,
          enctype: 'multipart/form-data',
          processData: false,
          contentType: false,
          success: function (data) { 
               //console.log('data',data);
               if(data.STATUS){
                   $("#mdl_upload").modal('hide'); 
                   $("#dvExcel").html(`${data.MSG}`);   
               }else{
               	$("#mdl_upload").modal('hide');
                alert('Problemas al cargar los datos');
                $("#dvExcel").html(`${data.MSG}`);
               }
          },
          error: function (data) { console.log(data); $("#mdl_upload").modal('hide'); }
    });   
}

// reender de años
function reenderComboBox(){
	 $.getJSON('params.json', function(jd) {
		var htm = `<option value=''>--seleccionar--</option>`;
		for (var i = jd.anio_init; i < (jd.anio_fin+1); i++) {
			htm+=`<option value='${i}'>${i}</option>`;
		}
		$("#anio").html(htm);
	 });
	// al cargar archivo
	 document.getElementById("estruct").addEventListener("change", () => {
                document.getElementById("estructlabel").innerHTML = document.getElementById("estruct").value.split("\\")[document.getElementById("estruct").value.split("\\").length - 1];
            })
}