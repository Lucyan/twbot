# Controlador de los bots.
class BotsController < ApplicationController
  before_filter :recuperar_bot, :only => [:editar, :actualizar, :bot_on, :bot_off, :palabras, :agregar_palabra, :eliminar, :guardar_palabra, :ciudades, :tweets, :tweet_detalle, :unfollow, :follow, :renovar, :plus]

  # Recupera bot según parametro de url
  def recuperar_bot
    begin
      @bot = Bot.find(params[:id])

      # Verifica si el usuario que está consultando el bot corresponde al de la sessión (solo lso admin pueden ver todo)
      user = User.find(session[:login]);
      if user.perfil == 0
        if @bot.user_id != user.id
          redirect_to(bot_path, :notice => "No tienes acceso al bot solicitado")
        end
      end
    rescue Exception => e
      redirect_to(bot_path, :notice => "Error: #{e}")
    end
  end

  # Despliega Listado todos los Bots
  def index
    session[:filtro] = nil
  	#@bots = Bot.all
    @user = User.find(session[:login]);
    @bots = @user.bots;
    if params['usuario'].present?
      if @user.perfil == 1
        usuario = User.find(params['usuario']);
        @bots = usuario.bots
      end
    end
  end

  # Despliega Formulario para gregar nuevo Bot
  def nuevo
    user = User.find(session[:login]);
    bots = user.bots
    logger.debug "Cantidad bot :#{bots.count}"

    if bots.count == user.cantidad_bots
      redirect_to(bot_path, :notice => "No puedes agregar otro Twitter, tu cuenta esta limitada a #{bots.count} FollowKeywords. Puedes solicitar un nuevo FollowKeywords en el boton \"Solicitar un nuevo FollowKeywords\" o desde tu perfil")
    end
  end

  # Guarda el nuevo Bot en la base de datos
  def guardar
  	@bot = Bot.new(params[:bot])
  	@bot.estado = 0
    @bot.siguiendo = 0
    @bot.seguidores = 0
    @bot.palabra_indice = 1
    @bot.palabra_maximo = 1
    @bot.ciudad_indice = 1
    @bot.user = session[:login]

    # Registro Plus para el primer bot
    user = User.find(session[:login]);
    if user.registro
      @bot.plus = true
      user.registro = false
      user.save
    end

    @twitter = Twitter::Client.new(
      :oauth_token => @bot.tw_token,
      :oauth_token_secret => @bot.tw_secret
    )
    @bot.followers_count = @twitter.user(@bot.tw_cuenta).follower_count

    # Se agrega fecha de renovación para controlar uso por fechas (1 mes)
    fecha = Time.new
    @bot.fecha_renovacion = fecha.strftime("%d-%m-%Y")

    # Restinge según variable de sistema
    @variable = Variable.all
    if Integer(@variable[0]['value']) >= Integer(params[:bot]['cantidad_seguir'])
      if @bot.valid?
        @bot.save
        redirect_to(bot_palabras_path(@bot), notice: 'Agrega palabras o frases a buscar')
      else
        flash[:error] = "Los datos del BOT no son validos, intenta nuevamente"
        render 'nuevo2'
      end
    else
      flash[:notice] = "ERROR: El Maximo a seguir es mayor a lo permitido"
      render 'nuevo2'
    end
  end

  # Obtiene autentificación de Twitter para el Bot
  def auth
  	#raise request.env["omniauth.auth"].to_yaml 
    @variable = Variable.all
  	begin
	  	auth = request.env["omniauth.auth"]
	  	if auth['credentials']['token']
        existe = Bot.find(:first, :conditions => {tw_cuenta: auth['info']['nickname']})
        logger.debug "The object is #{existe}"
        if existe
          flash[:error] = "ERROR: la cuenta que estas tratando de utilizar ya esta registrada en nuestros sistemas, recuerda que no puedes duplicar cuentas. Si estas seguro que deseas utilizar esta cuenta, contactate con los administradores"
          render 'nuevo'
        else
          @bot = Bot.new(nombre: auth['info']['name'], tw_cuenta: auth['info']['nickname'], tw_token: auth['credentials']['token'], tw_secret: auth['credentials']['secret'], estado: 0)
          render 'nuevo2'
        end
		  else
		    flash[:error] = "Error al autorizar al bot, intentalo nuevamente"
        render 'nuevo'
		  end
    rescue Exception => exc
      logger.debug "Error :#{exc.message}"
		  flash[:error] = "Error al autorizar al bot, intentalo nuevamente"
		  render 'nuevo'
    end
  end

  # En caso de fallar la autentificación, muestra error en pantalla
  def fail_auth
  	flash[:error] = "Error al autorizar al bot, intentalo nuevamente"
    render 'nuevo'
  end

  # Elimina Bot del Sistema
  def eliminar
    @bot.palabras.destroy_all
    @bot.botCiudads.destroy_all
    @bot.tweets.destroy_all
    @bot.destroy
    redirect_to(bot_path, :notice => "Bot Eliminado")
  end

  # Formulario Editar
  def editar
    @variable = Variable.all
  end

  # Formulario Editar
  def actualizar
    @variable = Variable.all
    if Integer(@variable[0]['value']) >= Integer(params[:bot]['cantidad_seguir'])
      if @bot.update_attributes(params[:bot])
        redirect_to(bot_path, :notice => "Bot Actualizado")
      else
        render 'editar'
      end
    else
      flash[:notice] = "ERROR: El Maximo a seguir es mayor a lo permitido"
      render 'editar'
    end
  end

  # Encender bot
  def bot_on
    @bot.estado = 1
    @bot.save
    redirect_to(bot_path, :notice => "Bot Encendido")
  end

  # Apagar bot
  def bot_off
    @bot.estado = 0
    @bot.save
    redirect_to(bot_path, :notice => "Bot Apagado")
  end

  # Despliega listado de Palabras de un Bot
  def palabras
  end

  # Despliega Formulario para agregar Palabra a un Bot
  def agregar_palabra
    @variable = Variable.all
    @palabra = @bot.palabras.new
  end

  # Guarda nueva Palabra a un Bot
  def guardar_palabra
    @variable = Variable.all
    @palabra = @bot.palabras.new(params[:palabra])
    if @palabra.valid?
      @palabra.save
      redirect_to(bot_palabras_path(@bot), :notice => "Palabra Agregada")
    else
      flash[:error] = "Ingresa una palabra o frase valida"
      render 'agregar_palabra'
    end
  end

  # Elimina Palabra de un Bot
  def eliminar_palabra
    @palabra = Palabra.find(params[:palabra_id])
    @palabra.destroy
    bot = Bot.find(params[:id])
    if bot.palabras.length == 0
      bot.update_attributes(estado: 0)
    end
    redirect_to(bot_palabras_path(params[:id]), :notice => "Palabra Eliminada")
  end

  # Muestra las ciudades asociadas al bot y disponibles para modificación
  def ciudades
    @ciudades = Ciudad.all
  end

  # Agregar Ciudad al Bot
  def agregar_ciudad
    begin
      ciudad = Ciudad.find(params[:id_ciudad])

      BotCiudad.create(bot_id: params[:id], ciudad_id: params[:id_ciudad])
      redirect_to(bot_ciudades_path(params[:id]), notice: "Ciudad Agregada")
    rescue Exception => e
      redirect_to(bot_path, :notice => "Error: #{e}")
    end
  end

  # Eliminar Ciudad del Bot
  def eliminar_ciudad
    begin
      @botciudad = BotCiudad.find(params[:id_botciudad])
      @botciudad.destroy
      bot = Bot.find(params[:id])
      if bot.botCiudads.length == 0
        bot.update_attributes(estado: 0)
      end
      redirect_to(bot_ciudades_path(params[:id]), notice: "Ciudad Eliminada")
    rescue Exception => e
      redirect_to(bot_path, :notice => "Error: #{e}")
    end
  end

  # Mustra listado de las personas que se han seguido
  def tweets
    if session[:filtro] && !params[:filtro]
      params[:filtro] = session[:filtro]
    end
    @filtro = ''
    if params[:filtro] && params[:filtro] != ''
      @tweets = Tweet.paginate(:conditions => ['bot_id = ? and estado = ?', @bot.id, params[:filtro]], :order => 'created_at DESC', :per_page => 20, :page => params[:page])
      @filtro = params[:filtro]
      session[:filtro] = params[:filtro]
    else
      session[:filtro] = nil
      @tweets = Tweet.paginate(:conditions => ['bot_id = ?', @bot.id], :order => 'created_at DESC', :per_page => 20, :page => params[:page])
    end
  end

  # Muestra detalle de un tweet
  def tweet_detalle
    @tweet = Tweet.find(params[:tweet_id])
  end

  # unfollow a personas manualmente
  def unfollow
    @twitter = Twitter::Client.new(
      :oauth_token => @bot.tw_token,
      :oauth_token_secret => @bot.tw_secret
    )

    @tweet = Tweet.find(params[:tweet])

    @twitter.unfollow(@tweet.tw_usuario)
    @tweet.estado = 4
    @tweet.save

    mensaje = "Dejo de seguir a " + @tweet.tw_usuario
    redirect_to(bot_tweets_path(@bot), notice: mensaje)
  end

  # follow a personas manualmente
  def follow
    @twitter = Twitter::Client.new(
      :oauth_token => @bot.tw_token,
      :oauth_token_secret => @bot.tw_secret
    )

    @tweet = Tweet.find(params[:tweet])

    @twitter.follow(@tweet.tw_usuario)
    @tweet.estado = 1
    @tweet.save

    mensaje = "Se sigue a " + @tweet.tw_usuario
    redirect_to(bot_tweets_path(@bot), notice: mensaje)
  end

  # renueva bots por 31 días
  def renovar
    # Se agrega fecha de renovación para controlar uso por fechas (1 mes)
    fecha = Time.new
    @bot.fecha_renovacion = fecha.strftime("%d-%m-%Y")

    if @bot.save
      redirect_to(:back, :notice => "Bot Renovado")
    else
      redirect_to(:back, :notice => "Error al intentar renovar, reintentalo")
    end
  end

  # Activa o desactiva Plus en bot
  def plus
    logger.debug "Bot antes: #{@bot.plus}"

    if @bot.plus == false
      @bot.plus = true
      mensaje = "Plus Activado"

      # Aviso a administradores del upgrade a Plus
      user = User.find(session[:login]);
      if user.perfil == 0
        UserMailer.upgrade_bot(user, @bot).deliver
      end

    else
      @bot.plus = false
      mensaje = "Plus Desactivado"
    end

    logger.debug "Bot despues: #{@bot.plus}"

    if @bot.save
      redirect_to(:back, :notice => mensaje)
    else
      redirect_to(:back, :notice => "Error al intentar Activar/Desactivar Plus, intentalo nuevamente")
    end
  end

  # Solicitar nuevo bot
  def solicitar
    user = User.find(session[:login]);
    UserMailer.solicitar_bot(user).deliver
    redirect_to :back, :notice => "Se ha solicitado un nuevo bot para tu cuenta, espera respuesta del administrador"
  end
end
