# Modelo de usuarios
class User < ActiveRecord::Base
  attr_accessible :email, :name, :password, :password_confirmation, :cantidad_bots, :perfil
  has_many :bots
  has_secure_password

  before_save { |user| user.email = email.downcase }

  validates :name,  presence: true, length: { maximum: 50 }
  VALID_EMAIL_REGEX = /\A[\w+\-.]+@[a-z\d\-.]+\.[a-z]+\z/i
  validates :email, presence: true, format: { with: VALID_EMAIL_REGEX }, uniqueness: { case_sensitive: false }
  validates :password, presence: true, length: { minimum: 6 }, :on => :create
  validates :password_confirmation, presence: true, :on => :create

end


# Crear Usuario
# User.create(name: "Admin", email: "contacto@reframe.cl", password: "RF123/()", password_confirmation: "RF123/()")