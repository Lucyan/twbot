class ApplicationController < ActionController::Base
  protect_from_forgery
  before_filter :autentificacion, :except => [:login, :registrar, :nuevo_usuario]

  # verifica autentificación
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

  # Login
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

  # Logout
  def logout
    reset_session
    redirect_to root_path
  end

  # Formulario de Registro
  def registrar
    if session[:login]
      redirect_to root_path
    else
      @user = User.new
    end
  end

  # Guarda Nuevo Usuario
  def nuevo_usuario
    @user = User.new(params[:user])
    @user.cantidad_bots = 1
    # Se agrega perfil de usuario (0)
    @user.perfil = 0
    if @user.valid?
      @user.save
      redirect_to(bot_path, :notice => "Usuario creado, ya puedes hacer login")
    else
      flash[:error] = "Error en los datos ingresados"
      render 'bots/registrar'
    end
  end

end
