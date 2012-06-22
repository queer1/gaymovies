<?php

$movie_list = init_movie_list(10);
print_movie_list($movie_list);
simulate_rankings($movie_list, 100000);

what_if( 100.0, 2300.0);
what_if( 200.0, 2300.0);
what_if( 400.0, 2300.0);
what_if( 800.0, 2300.0);
what_if( 900.0, 2300.0);
what_if(1600.0, 2300.0);
what_if(2000.0, 2300.0);
what_if(2100.0, 2300.0);
what_if(2200.0, 2300.0);
what_if(2300.0, 2200.0);
what_if(2300.0, 2100.0);
what_if(2300.0, 2000.0);
what_if(2300.0, 1600.0);
what_if(2300.0,  900.0);
what_if(2300.0,  800.0);
what_if(2300.0,  400.0);
what_if(2300.0,  200.0);
what_if(2300.0,  100.0);

print_movie_list($movie_list);

function what_if($ranking1, $ranking2)
{
  $sav1 = $ranking1;
  $sav2 = $ranking2;
  score($ranking1, $ranking2);
  $diff1 = $ranking1 - $sav1;
  $diff2 = $ranking2 - $sav2;

  print("If $sav1 beats $sav2; winner gets $diff1, loser loses $diff2\n");
}

function init_movie_list($numMovies)
{
  $movie_list = array();

  for ($ii = 0; $ii < $numMovies; $ii++)
  {
    $title        = "Movie$ii";
    $target       = $ii;
    $initial      = 1200.0;
    $ranking      = $initial;
    $movie_list[] = add_movie($title, $target, $initial, $ranking);
  }
  return $movie_list;
}

function print_movie_list($movie_list)
{
  $numMovies = count($movie_list);
  for ($ii = 0; $ii < $numMovies; $ii++)
  {
    print_movie($movie_list[$ii]);
  }
}

function print_movie($movie)
{
  $title = $movie['Title'];
  $target = $movie['Target'];
  $initial = $movie['Initial'];
  $ranking= $movie['Ranking'];
  $numRatings= $movie['NumRatings'];
  echo "$title, $target, $initial, $ranking, $numRatings\n";
}

function add_movie($title, $target, $initial, $ranking)
{
  $entry = array();
  $entry['Title']      = "$title"; 
  $entry['Target']     = $target; 
  $entry['Initial']    = $initial; 
  $entry['Ranking']      = $ranking;
  $entry['NumRatings'] = 0;
  return $entry;
}

function simulate_rankings(&$movie_list, $count)
{
  for ($ii = 0; $ii < $count; $ii++)
  {
    select_movies($movie_list, $idx1, $idx2); 
    //print("\n");
    //print_movie($movie1);
    //print_movie($movie2);
    if ($movie_list[$idx1]['Target'] < $movie_list[$idx2]['Target'])
      score($movie_list[$idx1]['Ranking'], $movie_list[$idx2]['Ranking']);
    else
      score($movie_list[$idx2]['Ranking'], $movie_list[$idx1]['Ranking']);

    $movie_list[$idx1]['NumRatings']++;
    $movie_list[$idx2]['NumRatings']++;
    
    //print_movie($movie1);
    //print_movie($movie2);
  }

}

function select_movies($movie_list, &$idx1, &$idx2)
{
  $max = count($movie_list) - 1;
  $idx1 = mt_rand(0, $max);
  $max--;
  $idx2 = mt_rand(0, $max);
  if ($idx2 >= $idx1)
    $idx2++;
}

function random_number($max)
{
  $random = mt_rand() % $max;
  return $random;
}

$denom = 400;
$kValue = 32.0;
function score(&$winner, &$loser, $draw=false)
{
  global $kValue;
  $kValue = 32.0;

  $eWinner = expected_score($winner, $loser);
  $eLoser  = expected_score($loser, $winner);
  if ($draw) {
    $deltaWinner = $kValue * (0.5 - $eWinner);
    $deltaLoser  = $kValue * (0.5 - $eLoser);
  } 
  else {
    $deltaWinner = $kValue * (1.0 - $eWinner);
    $deltaLoser  = $kValue * (0.0 - $eLoser);
  }

  //print("EWin: $eWinner ELose: $eLoser DeltaWin: $deltaWinner, DeltaLose: $deltaLoser\n");

  $winner += $deltaWinner;
  if ($winner > 2400.0)
    $winner = 2400.0;
  $loser += $deltaLoser;
  if ($loser < 100.0)
    $loser = 100.0;
  return;
}

function expected_score($player, $opponent)
{
  global $denom;
  $denom = 400.0;

  $exponent = ($opponent - $player)/$denom;
  $exponentResult = pow(10,$exponent);
  $value = 1.0 / (1.0 + $exponentResult);
  //print("EXP: $exponent, EXPRes: $exponentResult CHANGE: $value\n");
  return ($value);
}




?>
