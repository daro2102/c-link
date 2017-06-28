var config = {
	
	server: 'http://sck.cal24.pl/serwis/'
	
};


document.addEventListener('deviceready', init, false);
// init();

function init() {
	
	$(document).ready(function() {
	//$(document).bind('mobileinit', function() {
		
		
		//sprawdzenie czy mamy zapisany login i hasło
		var loginSt = window.localStorage.getItem('login');
		var passwordSt = window.localStorage.getItem('password');
		
		if( loginSt != null )
			$('#flogin').val(loginSt);
		
		if( passwordSt != null )
			$('#fpassword').val(passwordSt);
		
		
		//wpisanie w formularz domyślnego serwera
		$('#fserver').val(config.server);
		
		
		// obsługa formularza logowania
		$("#form_login").submit(function(e) {
			
			//przypisanie serwera z formularza
			config.server = $('#fserver').val();
			
			
			var login = $('#flogin').val();
			var password = $('#fpassword').val();
			
			var good = true;
			
			if( login.length == 0 ) {
				good = false;
				$('#flogin').addClass('error');
			} else
				$('#flogin').removeClass('error');
			
			if( password.length == 0 ) {
				good = false;
				$('#fpassword').addClass('error');
			} else
				$('#fpassword').removeClass('error');
			
			if(good) {
				
				
				window.localStorage.setItem('login', login);
				window.localStorage.setItem('password', password);
				
				api('login', { login:login, password:password },
					function(data) {
						
						navigator.vibrate(1000);
						
						$('#lclient_cars').html('');
						
						$.each(data.cars, function(index, item) {
							
							$('#lclient_cars').append('<li>\
								<a href="#page_car?car_id='+ item.id +'">\
									<h2>'+ item.mark +' / '+ item.model +' ('+ item.year +')</h2>\
									<p>Następne badanie: '+ item.nextinspection +'</p>\
								</a>\
							</li>');
							
						});
						
						$.mobile.changePage('#page_carList');
						
					});
				
			}
			
			
			// powstrzymanie przed domyślnym działaniem formularza
			e.preventDefault();
			e.stopPropagation();
			return false;
		});
		
		
		
		
		
		
		
		
		// Zdarzenia przed wejsciem na podstrone i wyciąganie parametrów za pomocą plugin'a
		$(document).on("pagecontainerbeforechange", function( event, ui ) {
			
			if ( typeof ui.toPage !== "string" ) {
				
				switch(ui.toPage[0].id) {
					
					//wczytywanie danych konkretnego samochodu (historia wizyt) w celu wyświetlenia ich
					case 'page_car':
						
						api('getCarData', { car_id: ui.options.pageData.car_id },
							function(data) {
								
								$('#page_car h2.title').html( data.mark +' / '+ data.model +' ('+ data.year +')' );
								$('#page_car p.nextinspection > span').html( data.nextinspection );
								
								$('#aset_visit').attr('href', '#page_setVisit?car_id=' + data.id);
								
								$('#lvisit_future').html('');
						
								$.each(data.visit_future, function(index, item) {
									
									$('#lvisit_future').append('<li>\
										<a href="#page_visitFuture?visit_id='+ item.id +'">\
											<h2>Data: '+ item.date +'</h2>\
											<p>'+ (item.confirmed == '1' ? 'Potwierdzone' : 'Oczekuje na potwierdzenie') +'</p>\
										</a>\
									</li>');
									
								});
								
								$('#lvisit_future').listview('refresh');
								
								
								$('#lvisit_past').html('');
						
								$.each(data.visit_past, function(index, item) {
									
									$('#lvisit_past').append('<li>\
										<a href="#page_visitPast?visit_id='+ item.id +'">\
											<h2>Data: '+ item.date +'</h2>\
											<p>Opis: '+ item.description +'</p>\
										</a>\
									</li>');
									
								});
								
								$('#lvisit_past').listview('refresh');
								
								
								//$.mobile.changePage('#page_carList');
								
							});
						
						break;
						
					//wczytywanie danych konkretnej wizyty (nadchodzącej)
					case 'page_visitFuture':
						
						api('getVisitFutureData', { visit_id: ui.options.pageData.visit_id },
							function(data) {
								
								$('#page_visitFuture h2.title > span').html( data.date );
								$('#page_visitFuture p.confirmed').html( (data.confirmed == '1' ? 'Potwierdzone' : 'Oczekuje na potwierdzenie') );
								
								if( typeof data.replacement_car == 'object' ) {
									$('#page_visitFuture h3.replacement_car_title').html('Wybrany samochód zastępczy:');
									$('#page_visitFuture p.replacement_car_name').html( data.replacement_car.mark + ' / ' + data.replacement_car.model );
								} else {
									$('#page_visitFuture h3.replacement_car_title').html('Brak samochodzu zastępczego');
									$('#page_visitFuture p.replacement_car_name').html('');
								}
								
							});
						
						break;
					
					//wczytywanie danych konkretnej wizyty (przeszłej)
					case 'page_visitPast':
						
						api('getVisitPastData', { visit_id: ui.options.pageData.visit_id },
							function(data) {
								
								$('#page_visitPast h2.title > span').html( data.date );
								$('#page_visitPast p.mileage > span').html( data.mileage );
								$('#page_visitPast p.description > span').html( data.description );
								
								if( typeof data.replacement_car == 'object' ) {
									$('#page_visitPast h3.replacement_car_title').html('Wybrany samochód zastępczy:');
									$('#page_visitPast p.replacement_car_name').html( data.replacement_car.mark + ' / ' + data.replacement_car.model );
								} else {
									$('#page_visitPast h3.replacement_car_title').html('Brak samochodzu zastępczego');
									$('#page_visitPast p.replacement_car_name').html('');
								}
								
							});
						
						break;
					
					//ustawienie id samochodu w formularzu umawiania wizyty i pobranie dostępności samochodów zastępczych dla domyślnej daty wizity
					case 'page_setVisit':
						
						$('#fcarId').val( ui.options.pageData.car_id );
						
						api('getSetDefaultVisitData', { car_id: ui.options.pageData.car_id },
							function(data) {
								
								$('#fdate').val( data.date );
								
								$('#freplacementcar').html('<option value="1">Bez samochodu zastępczego</option>');
								
								$.each(data.replacement_cars, function(index, item) {
									
									$('#freplacementcar').append('<option value="'+ item.id +'">' + item.mark + ' / ' + item.model + '</option>');
									
								});
								
								$( "#freplacementcar" ).selectmenu( "refresh" );
								
							});
						
						break;
					
				}
				
			}
			
		});
		
		
		
		
		// formularzu umawiania wizyty - ustawienia i zdarzenia
		$("#fdate").datepicker({dateFormat: "yy-mm-dd"});
		
		// reakcja na zmiane daty - pobranie danych dostępnych samochodów zastępczych w danym dniu
		$("#fdate").change(function(e) {
			
			api('getSetCustomVisitData', { date: $("#fdate").val() },
				function(data) {
					
					//$('#fdate').val( data.date );
					
					$('#freplacementcar').html('<option value="1">Bez samochodu zastępczego</option>');
					
					$.each(data.replacement_cars, function(index, item) {
						
						$('#freplacementcar').append('<option value="'+ item.id +'">' + item.mark + ' / ' + item.model + '</option>');
						
					});
					
					$( "#freplacementcar" ).selectmenu( "refresh" );
					
				});
			
		})
		
		// obsługa formularza zgłoszenia wizyty
		$("#form_setvisit").submit(function(e) {
			
			// powstrzymanie przed domyślnym działaniem formularza
			e.preventDefault();
			e.stopPropagation();
			
			
			var car_id = $('#fcarId').val();
			var date = $('#fdate').val();
			var replacementcar_id = $('#freplacementcar').val();
			
			/*var good = true;
			
			if( login.length == 0 ) {
				good = false;
				$('#flogin').addClass('error');
			} else
				$('#flogin').removeClass('error');
			
			if( password.length == 0 ) {
				good = false;
				$('#fpassword').addClass('error');
			} else
				$('#fpassword').removeClass('error');
			
			if(good) {*/
				
				api('setFormVisitData', { car_id:car_id, date:date, replacementcar_id:replacementcar_id },
					function(data) {
						
						$.mobile.changePage('#page_car?car_id='+data.car_id);
						
					});
				
			//}
			
			
			// powstrzymanie przed domyślnym działaniem formularza - dalsza część
			return false;
		});
	
	});
//}, false);
};






var api_result_code = {
	
	0: 'OK_RESULT',
	
	1: 'INVALID_DATA_ERROR',
	2: 'INCORRECT_DATA_ERROR',
	3: 'INTERNAL_DATA_ERROR',
	
	100: 'NO_LOGGED_ERROR'
	
};


function api(mode, data, succes) {
	
	
	if( checkConnection() ) {
		
		data.device = device.uuid;
		data.mode = mode;

		$.ajax({
			url: config['server']+'api.php',
			type: "POST",
			data: data,
			dataType: 'JSON',
			crossDomain: true
		}).done(function(data) {
			
			if(data['code'] == 0) {
				
				//console.log(data);
				
				succes( data['data'] );
				
			} else {
				
				//console.log(api_result_code[data['code']]);
				
			}
			
		}).fail(function() {
			//$('#info > div > p').html('Nie udane połączenie z serwerem, spróbuj jeszcze raz!');
			//$('#info').fadeIn();
		});
	
	} else {
		
		alert('Brak połączenia z internetem!');
		
	}
}




function checkConnection() {
    var networkState = navigator.connection.type;

    /*var states = {};
    states[Connection.UNKNOWN]  = 'Unknown connection';
    states[Connection.ETHERNET] = 'Ethernet connection';
    states[Connection.WIFI]     = 'WiFi connection';
    states[Connection.CELL_2G]  = 'Cell 2G connection';
    states[Connection.CELL_3G]  = 'Cell 3G connection';
    states[Connection.CELL_4G]  = 'Cell 4G connection';
    states[Connection.CELL]     = 'Cell generic connection';
    states[Connection.NONE]     = 'No network connection';

    alert('Connection type: ' + states[networkState]);*/
	
	if( networkState == Connection.NONE )
		return false;
	
	return true;
}