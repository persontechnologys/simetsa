<div class="sidebar sidebar-dark sidebar-main sidebar-expand-lg">

	<!-- Sidebar content -->
	<div class="sidebar-content">

		<!-- Sidebar header -->
		<div class="sidebar-section">
			<div class="sidebar-section-body d-flex justify-content-center">
				<h5 class="sidebar-resize-hide flex-grow-1 my-auto">Navegación</h5>

				<div>
					<button type="button" class="btn btn-flat-white btn-icon btn-sm rounded-pill border-transparent sidebar-control sidebar-main-resize d-none d-lg-inline-flex">
						<i class="ph-arrows-left-right"></i>
					</button>

					<button type="button" class="btn btn-flat-white btn-icon btn-sm rounded-pill border-transparent sidebar-mobile-main-toggle d-lg-none">
						<i class="ph-x"></i>
					</button>
				</div>
			</div>
		</div>
		<!-- /sidebar header -->

		<!-- Main navigation -->
		<div class="sidebar-section">
			<ul class="nav nav-sidebar" data-nav-type="accordion">

				<!-- Main -->
				<li class="nav-item-header pt-0">
					<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Principal</div>
					<i class="ph-dots-three sidebar-resize-show"></i>
				</li>
				<li class="nav-item">
					<a href="{{ route('dashboard') }}" class="nav-link {{ Route::is('dashboard')?'active': '' }}">
						<i class="ph-house"></i>
						<span>
							Dashboard
						</span>
					</a>
				</li>


				{{-- Seguridad y acceso --}}
				@canany(['usuarios.ver', 'roles.ver', 'accesos.ver'])
				<li class="nav-item nav-item-submenu {{ Route::is(['usuarios.*', 'roles.*']) ? 'nav-item-expanded nav-item-open' : '' }}">
					<a href="#" class="nav-link">
						<i class="ph-users"></i>
						<span>Seguridad y acceso</span>
					</a>
					<ul class="nav-group-sub collapse {{ Route::is(['usuarios.*', 'roles.*']) ? 'show' : '' }}">
						@can('usuarios.ver')
							<li class="nav-item"><a href="{{ route('usuarios.index') }}" class="nav-link {{ Route::is('usuarios.*') ? 'active' : '' }}">Usuarios</a></li>
						@endcan
						@can('roles.ver')
							<li class="nav-item"><a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">Roles y permisos</a></li>
						@endcan
					</ul>
				</li>
				@endcanany

				{{-- Catálogos --}}
				@canany(['zonas.ver', 'calles.ver', 'tarifas.ver', 'horarios.ver', 'parametros.ver','tipos_plaza.ver','feriados.ver', 'manzanas.ver'])

				<li class="nav-item nav-item-submenu {{ Route::is(['zonas.*','parametros.*', 'tipos-plaza.*','horarios-operacion.*','dias-feriado.*','tarifas.*','calles.*','manzanas.*','plazas.*']) ? 'nav-item-expanded nav-item-open' : '' }}">
					<a href="#" class="nav-link">
						<i class="ph-list"></i>
						<span>Catálogos</span>
					</a>
					<ul class="nav-group-sub collapse {{ Route::is(['zonas.*','parametros.*', 'tipos-plaza.*','horarios-operacion.*','dias-feriado.*','tarifas.*','calles.*', 'manzanas.*','plazas.*']) ? 'show' : '' }}">
						@can('zonas.ver')
							<li class="nav-item"><a href="{{ route('zonas.index') }}" class="nav-link {{ Route::is('zonas.*') ? 'active' : '' }}">Zonas</a></li>
						@endcan
						@can('calles.ver')
							<li class="nav-item"><a href="{{ route('calles.index') }}" class="nav-link {{ Route::is('calles.*') ? 'active' : '' }}">Calles</a></li>
						@endcan
						@can('manzanas.ver')
							<li class="nav-item"><a href="{{ route('manzanas.index') }}" class="nav-link {{ Request::routeIs('manzanas.*') ? 'active' : '' }}">Manzanas</a></li>
						@endcan
						@can('plazas.ver')
							<li class="nav-item"><a href="{{ route('plazas.index') }}" class="nav-link {{ Route::is('plazas.*') ? 'active' : '' }}">Plazas</a></li>
						@endcan
						@can('tarifas.ver')
							<li class="nav-item"><a href="{{ route('tarifas.index') }}" class="nav-link {{ Route::is('tarifas.*') ? 'active' : '' }}">Tarifas</a></li>
						@endcan
						
						@can('parametros.ver')
							<li class="nav-item"><a href="{{ route('parametros.index') }}" class="nav-link {{ Route::is('parametros.*') ? 'active' : '' }}">Parámetros</a></li>
						@endcan

						@can('tipos_plaza.ver')
							<li class="nav-item"><a href="{{ route('tipos-plaza.index') }}" class="nav-link {{ Route::is('tipos-plaza.*') ? 'active' : '' }}">Tipos de plaza</a></li>
						@endcan
						@can('horarios.ver')
							<li class="nav-item"><a href="{{ route('horarios-operacion.index') }}" class="nav-link {{ Route::is('horarios-operacion.*') ? 'active' : '' }}">Horarios</a></li>
						@endcan

						@can('feriados.ver')
							<li class="nav-item"><a href="{{ route('dias-feriado.index') }}" class="nav-link {{ Route::is('dias-feriado.*') ? 'active' : '' }}">Días feriados</a></li>
						@endcan
					</ul>
				</li>
				@endcanany

				{{-- Conductores y vehículos --}}
				@canany(['conductores.ver', 'tipos_vehiculo.ver', 'vehiculos.ver', 'vehiculos_exonerados.ver', 'credenciales_discapacidad.ver'])
				<li class="nav-item nav-item-submenu {{ Route::is(['conductores.*', 'tipos-vehiculo.*', 'vehiculos.*', 'vehiculos-exonerados.*', 'credenciales-discapacidad.*']) ? 'nav-item-expanded nav-item-open' : '' }}">
					<a href="#" class="nav-link">
						<i class="ph-car"></i>
						<span>Conductores y vehículos</span>
					</a>
					<ul class="nav-group-sub collapse {{ Route::is(['conductores.*', 'tipos-vehiculo.*', 'vehiculos.*', 'vehiculos-exonerados.*', 'credenciales-discapacidad.*']) ? 'show' : '' }}">
						@can('conductores.ver')
							<li class="nav-item"><a href="{{ route('conductores.index') }}" class="nav-link {{ Route::is('conductores.*') ? 'active' : '' }}">Conductores</a></li>
						@endcan
						@can('tipos_vehiculo.ver')
							<li class="nav-item"><a href="{{ route('tipos-vehiculo.index') }}" class="nav-link {{ Route::is('tipos-vehiculo.*') ? 'active' : '' }}">Tipos de vehículo</a></li>
						@endcan
						@can('vehiculos.ver')
							<li class="nav-item"><a href="{{ route('vehiculos.index') }}" class="nav-link {{ Route::is('vehiculos.*') ? 'active' : '' }}">Vehículos</a></li>
						@endcan
						@can('vehiculos_exonerados.ver')
							<li class="nav-item"><a href="{{ route('vehiculos-exonerados.index') }}" class="nav-link {{ Route::is('vehiculos-exonerados.*') ? 'active' : '' }}">Vehículos exonerados</a></li>
						@endcan
						@can('credenciales_discapacidad.ver')
							<li class="nav-item"><a href="{{ route('credenciales-discapacidad.index') }}" class="nav-link {{ Route::is('credenciales-discapacidad.*') ? 'active' : '' }}">Credenciales CONADIS</a></li>
						@endcan
					</ul>
				</li>
				@endcanany

				{{-- agentes y punto de venta --}}
				@canany(['agentes.ver', 'puntos_venta.ver','cursos.ver','solicitudes_punto_venta.ver','solicitudes_agente.ver'])
				<li class="nav-item nav-item-submenu {{ Route::is(['solicitudes-agente.*', 'puntos-venta.*','cursos-capacitacion.*', 'agentes-parqueo.*','solicitudes-punto-venta.*']) ? 'nav-item-expanded nav-item-open' : '' }}">
					<a href="#" class="nav-link">
						<i class="ph ph-shopping-cart"></i>
						<span>Agentes y puntos de venta</span>
					</a>
					<ul class="nav-group-sub collapse {{ Route::is(['solicitudes-agente.*', 'puntos-venta.*','cursos-capacitacion.*', 'agentes-parqueo.*','solicitudes-punto-venta.*']) ? 'show' : '' }}">
						@can('agentes.ver')
							<li class="nav-item"><a href="{{ route('solicitudes-agente.index') }}" class="nav-link {{ Route::is('solicitudes-agente.*') ? 'active' : '' }}">Solicitudes de agente</a></li>
							<li class="nav-item"><a href="{{ route('cursos-capacitacion.index') }}" class="nav-link {{ Route::is('cursos-capacitacion.*') ? 'active' : '' }}">Cursos de capacitación</a></li>
							<li class="nav-item"><a href="{{ route('agentes-parqueo.index') }}" class="nav-link {{ Route::is('agentes-parqueo.*') ? 'active' : '' }}">Agentes de parqueo</a></li>
						@endcan
						@can('puntos_venta.ver')
							<li class="nav-item"><a href="{{ route('solicitudes-punto-venta.index') }}" class="nav-link {{ Route::is('solicitudes-punto-venta.*') ? 'active' : '' }}">Solicitudes de punto de venta</a></li>
							<li class="nav-item"><a href="{{ route('puntos-venta.index') }}" class="nav-link {{ Route::is('puntos-venta.*') ? 'active' : '' }}">Puntos de venta activos</a></li>
						@endcan
						{{-- Próximamente (siguientes sub-fases): Cursos, Agentes activos, Puntos de venta --}}
					</ul>
				</li>
				@endcanany

				{{-- operaciones --}}
				@canany(['tickets.ver', 'sesiones_parqueo.ver', 'cancelaciones.ver'])
				<li class="nav-item nav-item-submenu {{ Route::is(['tickets.*', 'sesiones-parqueo.*', 'cancelaciones.*']) ? 'nav-item-expanded nav-item-open' : '' }}">
					<a href="#" class="nav-link">
						<i class="ph-note-blank"></i>
						<span>Operaciones</span>
					</a>
					<ul class="nav-group-sub collapse {{ Route::is(['tickets.*', 'sesiones-parqueo.*', 'cancelaciones.*']) ? 'show' : '' }}">
						@can('tickets.ver')
							<li class="nav-item"><a href="{{ route('tickets.index') }}" class="nav-link {{ Route::is('tickets.*') ? 'active' : '' }}">Tickets</a></li>
						@endcan
						@can('sesiones_parqueo.ver')
							<li class="nav-item"><a href="{{ route('sesiones-parqueo.index') }}" class="nav-link {{ Route::is('sesiones-parqueo.*') ? 'active' : '' }}">Sesiones de Parqueo</a></li>
						@endcan
						@can('cancelaciones.ver')
							<li class="nav-item"><a href="{{ route('cancelaciones.index') }}" class="nav-link {{ Route::is('cancelaciones.*') ? 'active' : '' }}">Cancelaciones</a></li>
						@endcan
					</ul>
				</li>
				@endcanany



				{{-- pagos --}}
				@canany(['pagos.ver', 'liquidaciones.ver', 'conciliaciones.ver', 'comprobantes.ver'])
				<li class="nav-item nav-item-submenu {{ Route::is(['transacciones.*', 'liquidaciones.*', 'comprobantes.*']) ? 'nav-item-expanded nav-item-open' : '' }}">
					<a href="#" class="nav-link">
						<i class="ph-coins"></i>
						<span>Pagos</span>
					</a>
					<ul class="nav-group-sub collapse {{ Route::is(['transacciones.*', 'liquidaciones.*', 'comprobantes.*']) ? 'show' : '' }}">
						@can('pagos.ver')
							<li class="nav-item"><a href="{{ route('transacciones.index') }}" class="nav-link {{ Route::is('transacciones.*') ? 'active' : '' }}">Transacciones</a></li>
						@endcan
						@can('liquidaciones.ver')
							<li class="nav-item"><a href="{{ route('liquidaciones.index') }}" class="nav-link {{ Route::is('liquidaciones.*') ? 'active' : '' }}">Liquidaciones</a></li>
						@endcan
						@can('conciliaciones.ver')
							<li class="nav-item"><a href="#" class="nav-link">Conciliaciones</a></li>
						@endcan
						@can('comprobantes.ver')
							<li class="nav-item"><a href="{{ route('comprobantes.index') }}" class="nav-link {{ Route::is('comprobantes.*') ? 'active' : '' }}">Comprobantes</a></li>
						@endcan
					</ul>
				</li>
				@endcanany

				{{-- infracciones --}}
				@canany(['infracciones.ver', 'multas.ver', 'inmovilizaciones.ver', 'ordenes_pago.ver', 'impugnaciones.ver'])
				<li class="nav-item nav-item-submenu {{ Route::is(['infracciones.*', 'inmovilizaciones.*', 'ordenes-pago.*', 'impugnaciones.*']) ? 'nav-item-expanded nav-item-open' : '' }}">
					<a href="#" class="nav-link">
						<i class="ph-warning"></i>
						<span>Infracciones</span>
					</a>
					<ul class="nav-group-sub collapse {{ Route::is(['infracciones.*', 'inmovilizaciones.*', 'ordenes-pago.*', 'impugnaciones.*']) ? 'show' : '' }}">
						@can('infracciones.ver')
							<li class="nav-item"><a href="{{ route('infracciones.index') }}" class="nav-link {{ Route::is('infracciones.*') ? 'active' : '' }}">Infracciones</a></li>
						@endcan
						@can('multas.ver')
							<li class="nav-item"><a href="#" class="nav-link">Multas</a></li>
						@endcan
						@can('inmovilizaciones.ver')
							<li class="nav-item"><a href="{{ route('inmovilizaciones.index') }}" class="nav-link {{ Route::is('inmovilizaciones.*') ? 'active' : '' }}">Inmovilizaciones</a></li>
						@endcan
						@can('ordenes_pago.ver')
							<li class="nav-item"><a href="{{ route('ordenes-pago.index') }}" class="nav-link {{ Route::is('ordenes-pago.*') ? 'active' : '' }}">Órdenes de pago</a></li>
						@endcan
						@can('impugnaciones.ver')
							<li class="nav-item"><a href="{{ route('impugnaciones.index') }}" class="nav-link {{ Route::is('impugnaciones.*') ? 'active' : '' }}">Impugnaciones</a></li>
						@endcan
					</ul>
				</li>
				@endcanany


				{{-- Fiscalizacion --}}
				@canany(['turnos.ver', 'incidentes.ver'])
				<li class="nav-item nav-item-submenu {{ Route::is(['turnos.*']) ? 'nav-item-expanded nav-item-open' : '' }}">
					<a href="#" class="nav-link">
						<i class="ph-chart-pie"></i>
						<span>Fiscalización</span>
					</a>
					<ul class="nav-group-sub collapse {{ Route::is(['turnos.*']) ? 'show' : '' }}">
						@can('turnos.ver')
							<li class="nav-item"><a href="{{ route('turnos.index') }}" class="nav-link {{ Route::is('turnos.*') ? 'active' : '' }}">Turnos</a></li>
						@endcan
						@can('recorridos.ver')
							<li class="nav-item"><a href="#" class="nav-link">Recorridos</a></li>
						@endcan
						@can('incidentes.ver')
							<li class="nav-item"><a href="#" class="nav-link">Incidentes</a></li>
						@endcan
					</ul>
				</li>
				@endcanany


				{{-- Reportes --}}
				@canany(['reportes.ver', 'kpi.ver', 'auditoria.ver', 'accesos.ver'])
				<li class="nav-item nav-item-submenu {{ Route::is(['dashboard', 'reportes.*', 'kpi.*', 'auditoria.*', 'accesos.*']) ? 'nav-item-expanded nav-item-open' : '' }}">
					<a href="#" class="nav-link">
						<i class="ph-chart-pie"></i>
						<span>Reportes</span>
					</a>
					<ul class="nav-group-sub collapse {{ Route::is(['dashboard', 'reportes.*', 'kpi.*', 'auditoria.*', 'accesos.*']) ? 'show' : '' }}">
						@can('kpi.ver')
							<li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link {{ Route::is('dashboard')?'active':'' }}">Dashboard KPIs</a></li>
						@endcan
						@can('reportes.ver')
							<li class="nav-item"><a href="{{ route('reportes.recaudacion.index') }}" class="nav-link {{ Route::is('reportes.recaudacion.*')?'active':'' }}">Recaudación</a></li>
							<li class="nav-item"><a href="{{ route('reportes.infracciones.index') }}" class="nav-link {{ Route::is('reportes.infracciones.*')?'active':'' }}">Infracciones</a></li>
							<li class="nav-item"><a href="{{ route('reportes.ocupacion.index') }}" class="nav-link {{ Route::is('reportes.ocupacion.*')?'active':'' }}">Ocupación</a></li>
						@endcan
						@can('accesos.ver')
							<li class="nav-item"><a href="{{ route('accesos.index') }}" class="nav-link {{ Route::is('accesos.*')?'active':'' }}">Registro de accesos</a></li>
						@endcan
						@can('auditoria.ver')
							<li class="nav-item"><a href="#" class="nav-link">Auditoría</a></li>
						@endcan
					</ul>
				</li>
				@endcanany

				

			</ul>
		</div>
		<!-- /main navigation -->
	</div>
	<!-- /sidebar content -->
</div>
