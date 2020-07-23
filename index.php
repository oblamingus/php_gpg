<?php

header('Content-Type: text/html; charset=UTF-8');

if ($_POST) {
#   echo '<pre>';
#   echo htmlspecialchars(print_r($_POST, true));
#   echo '</pre>';
  if (!isset($_POST['act'], $_POST['msg-json'])) {
    echo '<div class="error"> Ошибка запросе. Введены не все параметры</div>';
  } else {

    # файл json для отладки и удобного скачавания результата
    $file_name_json=uniqid('zz_',false).'.json';
    # файл asc - зашифрованый PGP для отладки и удобного скачавания результата
    $file_name_asc=uniqid('zz_',false).'.asc';

    if (file_put_contents($file_name_json,$_POST['msg-json'])===false) {
      echo '<div class="error">На сервере не удалось сохранить Файл JSON</div>';
    } else {
      echo '<div><a href="'.$file_name_json.'" target="balnk">'.$file_name_json.'</a></div>';
    }

   # Директория с установленными ключами
   putenv('GNUPGHOME=/home/app/.gnupg');
   # ВАЖНО!!!  Права доступа к файлам 600. 
   # Ваделец - пользователь от чьего имени работает web сервер. 
   # Возможно, это www-data

   $gpg = new gnupg();
   if ( !isset($gpg)) {
      echo '<div class="error">На сервере не настроен PHP GnuPG<br>ERROR:'.error_get_last()['message'].'</div>';
      exit();
   }
   # Получить сведения о ключе (для отладки)
   $info = $gpg -> keyinfo('CF8DBB34');
   echo '<pre>'.htmlspecialchars(print_r($_POST, true)).'</pre>';

   
   # В зависимости от выбора действия (Защифровать/Посписать)
  switch ($_POST['act']) {
     case "act_sign":
       # Подписываем своим приватным ключем (ID_KEY,PASSWORD)
       $gpg -> addsignkey("96CF7FFA","12345");
       $res_asc = $gpg->sign($_POST['msg-json']);
       echo '<div class="label">Результат: подписано PGP</div>';
       echo '<textarea rows="10" cols="50">'.$res_asc.'</textarea>';
       break;
     case "act_enc":
       # Шифруем публичныйм ключем получателя
       $gpg -> addencryptkey("CF8DBB34"); 
       $res_asc = $gpg -> encrypt($_POST['msg-json']);
       echo '<div class="label">Результат: зашифровано PGP</div>';
       echo '<textarea rows="10" cols="50">'.$res_asc.'</textarea>';
       break;
   }

  # Сохранияем зашифрованный/подписанный content
  if (file_put_contents($file_name_asc,$res_asc)===false) {
    echo '<div class="error">На сервере не удалось сохранить Файл ASC</div>';
  } else {
    echo '<div><a href="'.$file_name_asc.'" target="balnk">'.$file_name_asc.'</a></div>';
  }

  if (isset($_POST['email'])) {
    # Указали почту будем отправлять данные
    $subject='Оформлен новый заказ ';
    $headers_mail = array(
      'From' => 'webmaster@oriontronix.ru',
      'X-Mailer' => 'PHP/'.phpversion()
    );
   
    $success = mail($_POST['email'],$subject,$res_asc,$headers_mail);
    if ( !success ) {
      echo '<div class="error">Не удалось отправить письмо на '.$_POST['email'].'<br>ERROR:'.error_get_last()['message'].'</div>';
    } else {
      echo '<div>Письмо успешно отправлено на '.$_POST['email'].'</div>';
    }
  }   
  
  echo "<hr>";  
  
  }

}
?>

<form action="" method="post">
    <div class="label">Операция</div>
    <select name="act">
        <option value="act_enc">Зашифровать (нужен только публичный ключ получателя)</option>
        <option selected value="act_sign">Подписать (нужен приватный ключ и пароль)</option>
    </select>
    <div class="label">Сообщение</div>
    <textarea  name="msg-json" rows="10" cols="50">
{
  "id":"123",
  "your-name":"Иванов Иван Иванович", 
  "your-email":"ivanov@mail.ru",
  "text-inn":"7800000000",
  "menu-type-query":"Запрос на просчет",
  "end-customer":"Автоматика Сервис",
  "project-description":"Стенд проверки", 
  "item-order":[
    {
      "id":"123.001",
      "part-number":"1656725",
      "text-mfg": "VS-08-RJ45-5-Q/IP20",
      "number-cols":10,
      "number-eau":10000
    },
    {
      "id": "123.002",
      "part-number": "0567590001",
      "text-mfg": "TMS-SCE-3/4-2.0-9",
      "number-cols":2,
      "number-eau":150
    },
    {
      "id":"123.003",
      "part-number":"1SX1-T",
      "text-mfg":"78454956790",
      "number-cols":7,
      "number-eau":10
    },
    {
      "id": "123.004",
      "part-number": "DTM04-12PA",
      "text-mfg": "DTM04-12PA",
      "number-cols":1,
      "number-eau":100
    }
  ]
}
    </textarea>
    <div class="label">Послать результат на e-mail:</div>
    <input type="text" name="email" />
    <br />
    <input type="submit" value="Пуск" />
</form>

