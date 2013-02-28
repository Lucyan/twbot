# encoding: UTF-8
# This file is auto-generated from the current state of the database. Instead
# of editing this file, please use the migrations feature of Active Record to
# incrementally modify your database, and then regenerate this schema definition.
#
# Note that this schema.rb definition is the authoritative source for your
# database schema. If you need to create the application database on another
# system, you should be using db:schema:load, not running all the migrations
# from scratch. The latter is a flawed and unsustainable approach (the more migrations
# you'll amass, the slower it'll run and the greater likelihood for issues).
#
# It's strongly recommended to check this file into your version control system.

ActiveRecord::Schema.define(:version => 20130228031247) do

  create_table "bot_ciudades", :force => true do |t|
    t.integer  "bot_id"
    t.integer  "ciudad_id"
    t.datetime "created_at", :null => false
    t.datetime "updated_at", :null => false
  end

  create_table "bot_ciudads", :force => true do |t|
    t.integer  "bot_id"
    t.integer  "ciudad_id"
    t.datetime "created_at", :null => false
    t.datetime "updated_at", :null => false
  end

  create_table "bots", :force => true do |t|
    t.string   "nombre"
    t.string   "tw_cuenta"
    t.string   "tw_token"
    t.string   "tw_secret"
    t.integer  "estado"
    t.datetime "created_at",                             :null => false
    t.datetime "updated_at",                             :null => false
    t.integer  "siguiendo"
    t.integer  "seguidores"
    t.integer  "palabra_indice"
    t.integer  "palabra_maximo"
    t.integer  "ciudad_indice"
    t.integer  "cantidad_seguir"
    t.integer  "verificar_seguido"
    t.integer  "followers_count"
    t.integer  "user_id"
    t.date     "fecha_renovacion"
    t.string   "frase_al_seguir",     :default => ""
    t.string   "frase_cuando_siguen", :default => ""
    t.boolean  "plus",                :default => false
  end

  create_table "ciudads", :force => true do |t|
    t.string   "nombre"
    t.string   "longitud"
    t.string   "latitud"
    t.integer  "km"
    t.datetime "created_at", :null => false
    t.datetime "updated_at", :null => false
  end

  create_table "palabras", :force => true do |t|
    t.integer  "bot_id"
    t.string   "palabra"
    t.datetime "created_at",                 :null => false
    t.datetime "updated_at",                 :null => false
    t.string   "frase",      :default => ""
  end

  create_table "tweets", :force => true do |t|
    t.integer  "bot_id"
    t.string   "tw_usuario_id"
    t.string   "tw_tweet_id"
    t.string   "tw_location"
    t.string   "tw_usuario"
    t.string   "tw_text"
    t.string   "tw_created_at"
    t.integer  "estado"
    t.datetime "created_at",                     :null => false
    t.datetime "updated_at",                     :null => false
    t.string   "palabra"
    t.string   "ciudad"
    t.integer  "mensaje_enviado", :default => 0
  end

  create_table "users", :force => true do |t|
    t.string   "name"
    t.string   "email"
    t.datetime "created_at",                     :null => false
    t.datetime "updated_at",                     :null => false
    t.string   "password_digest"
    t.integer  "cantidad_bots",   :default => 1
    t.integer  "perfil",          :default => 0
  end

end
