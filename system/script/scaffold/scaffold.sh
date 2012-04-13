#!/bin/bash
#must run in this dir
#function: create new app

echo "please input project dir"
read root_dir

echo "please input new app name"
read app_name

echo "please input new action name"
read action_name

#root_dir=/var/www/html/cnzzadmin
app_dir=$root_dir/application/apps

#$app_name=$1
#$action_name=$2
new_app_path=$app_dir/$app_name

if [ ! -d $new_app_path ]
then
    mkdir $app_dir/$app_name
fi
cp -rf ./controllers ./actions ./views ./models ./templates  $new_app_path

#convert first alpha to upper
classname_app=`echo $app_name |sed 's/^[a-z]/\U&/'`
classname_action=`echo $action_name |sed 's/^[a-z]/\U&/'`
#echo $class_prefix

#cmd="sed -i s/{\\\$app}/$class_prefix/g" "\`grep -rinl '{\$app}' $new_app_path\`"
#cmd="sed -i \"s/{\\\$app}/$class_prefix/g\" \`grep -rinl '{\$app}' $new_app_path\`"
sed -i "s/{\\\$app}/$classname_app/g" `grep -rinl '{\$app}' $new_app_path`
sed -i "s/{\\\$action}/$classname_action/g" `grep -rinl '{\$action}' $new_app_path`

mv $new_app_path/controllers/controller.php $new_app_path/controllers/$app_name.controller.php
mv $new_app_path/actions/action.php $new_app_path/actions/$action_name.action.php
mv $new_app_path/views/view.php $new_app_path/views/$action_name.view.php
mv $new_app_path/models/model.php $new_app_path/models/$action_name.model.php
mv $new_app_path/templates/template.php $new_app_path/templates/$action_name.template.php

