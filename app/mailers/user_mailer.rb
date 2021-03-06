# Funciones de envío de mail
class UserMailer < ActionMailer::Base
	default from: "contacto@reframe.cl"

	# Mail de bienvenida al usuario
	def welcome_email(user)
    	@user = user
    	email_with_name = "#{@user.name} <#{@user.email}>"
    	mail(:to => email_with_name, :subject => "Bienvenido a FollowKeywords")
  	end

  	# Mail para solicitar bot
  	def solicitar_bot(user)
  		@user = user
  		mail(:to => User.where('perfil = 1').map(&:email), :subject => "Solicitud Nuevo Bot de: #{@user.name}")
  	end

  	# Mail upgrade plus
  	def upgrade_bot(user, bot)
  		@user = user
  		@bot = bot
  		mail(:to => User.where('perfil = 1').map(&:email), :subject => "Upgrade a Plus de #{@user.name}")
  	end
end
