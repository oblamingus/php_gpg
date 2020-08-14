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
      echo '<div><a href="'.$file_name_json.'" target="blank">'.$file_name_json.'</a></div>';
    }

   # Директория с установленными ключами
   putenv('GNUPGHOME=/home/app/.gnupg');
   # ВАЖНО!!!  Права доступа к файлам 600. 
   # Ваделец - пользователь от чьего имени работает web сервер. 
   # Возможно, это www-data

   $gpg = new gnupg() or exit("GnuPG library not found");

   # Получить сведения о ключе (для отладки)
   #$info = $gpg -> keyinfo('7301CFF4') or exit($gpg->geterror());
   #echo '<pre>'.htmlspecialchars(print_r($info, true)).'</pre>';

   
   # В зависимости от выбора действия (Защифровать/Посписать)
  switch ($_POST['act']) {
     case "act_sign":
       # Подписываем своим приватным ключем (ID_KEY,PASSWORD)
       $gpg -> addsignkey("7301CFF4","thinktwice") or exit($gpg->geterror());
       $res_asc = $gpg->sign($_POST['msg-json']) or exit($gpg->geterror());
       echo '<div class="label">Результат: подписано PGP</div>';
       echo '<textarea rows="10" cols="50">'.$res_asc.'</textarea>';
       break;
     case "act_enc":
       # Шифруем публичныйм ключем получателя
       $gpg -> addencryptkey("7301CFF4") or exit($gpg->geterror()); 
       $res_asc = $gpg -> encrypt($_POST['msg-json']) or exit($gpg->geterror());
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
    $to = $_POST['email'];
    $subject='Оформлен новый заказ ';
# не работает в PHP 5.3
#    $headers_mail = array(
#      'From' => 'webmaster@oriontronix.ru',
#      'X-Mailer' => 'PHP/'.phpversion(),
#      'Return-Path' => 'webmaster@oriontronix.ru'
#    );
    $headers_mail = 'From: order-script@www.oriontronix.ru'."\r\n".
       'X-Mailer: PHP/' . phpversion()."\r\n".
       'Return-Path: order-script@www.oriontronix.ru'."\r\n".
       'Content-type: text/plain; charset=utf-8';

    $success = mail($to,$subject,$res_asc,$headers_mail);
    if ( !$success ) {
      echo '<div class="error">Не удалось отправить письмо на '.$to.'</div>';
    } else {
      echo '<div>Письмо успешно отправлено на '.$to.'</div>';
    }
  }   
  
  echo '<hr>';  
  echo '<a href="/">В начало</a>';
  }

} else {
?>

<form action="" method="post">
    <div class="label">Операция</div>
    <select name="act">
        <option value="act_enc">Зашифровать (нужен только публичный ключ получателя)</option>
        <option selected value="act_sign">Подписать (нужен приватный ключ и пароль)</option>
    </select>
    <div class="label">Сообщение</div>
    <textarea  name="msg-json" rows="28" cols="100">
{
       "Id": "d7a9ed859e90682e724e77a3546910a4",
       "Type": "Bid",
         "Project": "ff",
         "Customer": "dd",
         "Address": "ООО \"Ромашка\"; Цветочная 1; Земледельческий район; 190000Zip; Колхозград; Область; Россия; ФИО; Телефон; 123456789ИНН",
         "Comment": "В свободной форме",
         "Note": "test@test.ru",
         "Items": [
                   {
                             "Id": "d7a9ed859e90682e724e77a3546910a4_0",
                             "ItmBrand": "dfgsdfsdf",
                             "ItmPartname": "2/22 2-B",
                             "ItmDescription": "",
                             "ItmQty": "1",
                             "ItmEAUQty": "1"
                   }
         ]
}
    </textarea>
    <div class="label">Послать результат на e-mail:</div>
    <input type="text" name="email" value="order-request@oriontronix.ru" size=40 />
    <br />
    <input type="submit" value="Пуск" />
</form>

<?php } ?>
