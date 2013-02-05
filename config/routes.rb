  Twbot::Application.routes.draw do
  get "users/index"

  root to: 'bots#index'

  match '/bot/nuevo' => 'bots#nuevo', :as => '/bot/nuevo', :via => :get
  match '/bot/nuevo' => 'bots#guardar', :as => '/bot/nuevo', :via => :post
  match '/bot/:id/editar' => 'bots#editar', :as => '/bot/editar', :via => :get
  match '/bot/:id/editar' => 'bots#actualizar', :as => '/bot/editar', :via => :put
  match '/bot/eliminar/:id' => 'bots#eliminar', :as => '/bot/eliminar'
  match '/bot/on/:id' => 'bots#bot_on', :as => '/bot/on'
  match '/bot/off/:id' => 'bots#bot_off', :as => '/bot/off'
  match '/bot/login' => 'bots#login', :as => '/bot/login', :via => :post
  match '/logout' => 'bots#logout'

  match '/bot/:id/palabras' => 'bots#palabras', :as => '/bot/palabras'
  match '/bot/:id/palabras/agregar' => 'bots#agregar_palabra', :as => '/bot/agregar/palabra', :via => :get
  match '/bot/:id/palabras/agregar' => 'bots#guardar_palabra', :as => '/bot/agregar/palabra', :via => :post
  match '/bot/:id/palabras/eliminar/:palabra_id' => 'bots#eliminar_palabra', :as => '/bot/eliminar/palabra'

  match '/bot/:id/ciudades' => 'bots#ciudades', :as => '/bot/ciudades'
  match '/bot/:id/ciudades/add/:id_ciudad' => 'bots#agregar_ciudad', :as => '/bot/ciudades/add'
  match '/bot/:id/ciudades/del/:id_botciudad' => 'bots#eliminar_ciudad', :as => '/bot/ciudades/del'

  match '/bot/:id/tweets' => 'bots#tweets', :as => '/bot/tweets'
  match '/bot/:id/tweets/detalle/:tweet_id' => 'bots#tweet_detalle', :as => '/bot/tweet/detalle'
  match '/bot/:id/tweets/unfollow/:tweet' => 'bots#unfollow', :as => '/bot/tweets/unfollow'
  match '/bot/:id/tweets/follow/:tweet' => 'bots#follow', :as => '/bot/tweets/follow'

  match "/auth/:provider/callback" => "bots#auth"
  match "/auth/failure" => "bots#fail_auth"

  match "/ciudades" => "ciudades#index"
  match "/ciudades/nueva" => "ciudades#nueva", :as => '/ciudades/nueva', :via => :get
  match "/ciudades/nueva" => "ciudades#guardar", :as => '/ciudades/nueva', :via => :post
  match "/ciudades/editar/:id" => "ciudades#editar", :as => '/ciudades/editar', :via => :get
  match "/ciudades/editar/:id" => "ciudades#actualizar", :as => '/ciudades/editar', :via => :put
  match "/ciudades/eliminar/:id" => "ciudades#eliminar", :as => '/ciudades/eliminar'

  match "/registro" => "bots#registrar", :as => '/registro', :via => :get
  match "/registro" => "bots#nuevo_usuario", :as => '/registro', :via => :post

  match "/usuarios" => "users#index"
  match '/usuarios/eliminar/:id' => 'users#eliminar', :as => '/usuarios/eliminar'
  match "/usuarios/:id/editar" => "users#editar", :as => '/usuarios/editar', :via => :get
  match "/usuarios/:id/editar" => "users#guardar_editado", :as => '/usuarios/editar', :via => :put
  match "/:usuario" => "bots#index", :as => '/usuarios/bots'

  # The priority is based upon order of creation:
  # first created -> highest priority.

  # Sample of regular route:
  #   match 'products/:id' => 'catalog#view'
  # Keep in mind you can assign values other than :controller and :action

  # Sample of named route:
  #   match 'products/:id/purchase' => 'catalog#purchase', :as => :purchase
  # This route can be invoked with purchase_url(:id => product.id)

  # Sample resource route (maps HTTP verbs to controller actions automatically):
  #   resources :products

  # Sample resource route with options:
  #   resources :products do
  #     member do
  #       get 'short'
  #       post 'toggle'
  #     end
  #
  #     collection do
  #       get 'sold'
  #     end
  #   end

  # Sample resource route with sub-resources:
  #   resources :products do
  #     resources :comments, :sales
  #     resource :seller
  #   end

  # Sample resource route with more complex sub-resources
  #   resources :products do
  #     resources :comments
  #     resources :sales do
  #       get 'recent', :on => :collection
  #     end
  #   end

  # Sample resource route within a namespace:
  #   namespace :admin do
  #     # Directs /admin/products/* to Admin::ProductsController
  #     # (app/controllers/admin/products_controller.rb)
  #     resources :products
  #   end

  # You can have the root of your site routed with "root"
  # just remember to delete public/index.html.
  # root :to => 'welcome#index'

  # See how all your routes lay out with "rake routes"

  # This is a legacy wild controller route that's not recommended for RESTful applications.
  # Note: This route will make all actions in every controller accessible via GET requests.
  # match ':controller(/:action(/:id))(.:format)'
end
