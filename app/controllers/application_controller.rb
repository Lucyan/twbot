# Controlador base, todas las acciones(funciones) que se definen aquí, son heredados por los otros controladores
class ApplicationController < ActionController::Base
  protect_from_forgery
  before_filter :autentificacion, :except => [:login, :registrar, :nuevo_usuario]

  # Verifica autentificación para enviarlo a login en caso de que la session esté expirada o no esté logeado
  def autentificacion
    if session[:login]
      if session[:last_seen] < 1.hour.ago
        reset_session
        render :template => 'bots/login'
      end
      session[:last_seen] = Time.now
    else
      render :template => 'bots/login'
    end
  end

  # Controlla logín en la aplicación, en caso de ser exitoso, genera session del usuario
  def login
    user = User.find_by_email(params[:session][:email].downcase)
    if user && user.authenticate(params[:session][:password])
      session[:login] = user
      session[:last_seen] = Time.now
      redirect_to bot_path
    else
      flash.now[:error] = 'Usuario o Password Incorrectos'
      render :template => 'bots/login'
    end
  end

  # Controla logout de la aplicación
  def logout
    reset_session
    redirect_to root_path
  end

  # Despliega el formulario de registro
  def registrar
    if session[:login]
      redirect_to root_path
    else
      @user = User.new
    end
  end

  # Controla el registro del usuario para guardarlo en la BD
  def nuevo_usuario
    @user = User.new(params[:user])
    @user.cantidad_bots = 1

    # Según tipo de registro
    if params[:tipo] == "plus"
      @user.registro = true
    end

    # Se agrega perfil de usuario (0)
    @user.perfil = 0
    if @user.valid?
      @user.save
      # Login automatico al registrar
      session[:login] = @user
      session[:last_seen] = Time.now
      UserMailer.welcome_email(@user).deliver
      redirect_to bot_path, :notice => "Registro OK, Bienvenido"
    else
      flash[:error] = "Error en los datos ingresados"
      render 'bots/registrar'
    end
  end

end
