class CreateUsers < ActiveRecord::Migration
  def up
    create_table :users do |t|
          t.string :profile_id
          t.string :email
          t.string :refresh_token
    end
    add_index :users, :profile_id
  end

  def down
    drop_table :users
  end
end
