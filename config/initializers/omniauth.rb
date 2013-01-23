Rails.application.config.middleware.use OmniAuth::Builder do  
  provider :twitter, 'rzXrHNkh5KnEWdW6VuDwhA', 'Ge7BpLLw3rh0JlXSWHaIklmfy4WhIYKaoyhPIF0XU'  
  # Added to config/initializers/omniauth.rb
	OmniAuth.config.on_failure = Proc.new { |env|
	  OmniAuth::FailureEndpoint.new(env).redirect_to_failure
	}
end
