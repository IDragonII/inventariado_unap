import LoginPage from 'pages/LoginPage.vue'
const routes = [
  {
    path: '/login',
    name: "login",
    component: LoginPage,
  },
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue') },
      { path: '/usuarios', component: () => import('pages/users/UsuariosPage.vue') },
      { path: '/activos', component: () => import('pages/Activos/ActivoPage.vue') },
      { path: 'activos/categorias', component: () => import('src/pages/Categorias/CategoriaPage.vue')},
      { path: '/perfil', component:() => import('pages/users/PerfilPage.vue')},
      { path: '/areas', component: () => import('src/pages/Areas/AreasPage.vue') },
      { path: '/oficinas', component: () => import('src/pages/Oficinas/OficinasPage.vue')},
      { path: '/entidades', component: () => import('pages/Entidades/EntidadesPage.vue')},
      { path: '/movimientos', component: () => import('pages/Movimientos/MovimientosPage.vue')},
      { path: '/configuracion', component: () => import ('pages/Configuracion/ItosDeclaracionesPage.vue')},
      { path: '/software', component: ()=> import ('pages/Software/SoftwarePage.vue')},
      { path: '/software/agregar-activo', component: ()=> import ('pages/Software/AgregarSoftwareActivoPage.vue')},
      { path: '/inventariador', component: () => import('pages/Inventariador/InventariadorPage.vue') },
    ]
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes
