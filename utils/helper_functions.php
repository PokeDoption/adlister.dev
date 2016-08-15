<?php
// List of helper functions used throughout the application.
// Primarily used within the PageController function.

function addMemberController() 
{
    if (isset($_POST['TEAM_DELETED'])) {
        deleteTeam();
    }
    if (isset($_POST['member'])) {
        addMember();
    }
}

function myTeamsController()
{
    $teamsArray = Team::findByUser($_SESSION['LOGGED_IN_ID']);
    foreach ($teamsArray->teams as $team) 
    {
        $myTeamsById[] = $team['id'];                
    }
    $_SESSION['USER_TEAMS'] = $myTeamsById;
}

function displayTeamById($id)
{
    $selected = Team::findByTeamId($id);
    return $selected->team;
}

function visitTeamController()
{
    $teamMembers = displayTeamMembers(Input::get('team'));
    $teamName = $teamMembers['teamName'];
    unset($teamMembers['teamName']);
    $memberNames = getMemberNames($teamMembers);
    $pokedexIds = getMemberPokedexNumbers($memberNames);
    return [
        'memberNames' => $memberNames,
        'stats' => $teamMembers,
        'teamName' => $teamName,
        'pokedexId' => $pokedexIds
    ];
}

function getMemberPokedexNumbers($teamMembers)
{
    foreach ($teamMembers as $member)
    {
        $data = Pokemon::selectStats($member, true);
        $teamIds[] = $data['Pokedex'];
    }
    return $teamIds;
}

function getMemberNames($team)
{
    foreach ($team as $name => $stat)
    {
        $memberNames[] = $name;
    }
    return $memberNames;
}

function displayTeamMembers($id) {
    $teamName = Team::getName($id);
    $fullTeam = TeamMember::findByTeamId($id);
    foreach ($fullTeam->members as $member) {
        $pokedexEntry = Pokemon::getPokemon($member['id']);
        $name = $pokedexEntry['Pokemon'];
        $pokemonStats = Pokemon::selectStats($member['id']);
        $allMembers[$name] = $pokemonStats;
    }
    $allMembers['teamName'] = $teamName;
    return $allMembers;
}

// function allTeamsController()
// {
//     $user = new User;
//     $user->id = $_SESSION['LOGGED_IN_ID'];

//     // foreach (team where user_id = loggedinid)
//         $user->teams[] = 
// }

function addTeamController()
{
    if (isset($_POST['TEAM_NAME']))
    {
        attemptTeamCreation();        
    } else
    {
        $_POST['MESSAGE'] = "Please enter a team name:";
    }
}
function attemptTeamCreation()
{
    $exists = ifExists();
    if ($exists)
    {    
        $_POST['MESSAGE'] = "Team Created! Select six members for your team:";
    }
    createTeam();
}

function ifExists() 
{
    $exists = Team::findByTeamName(Input::get('TEAM_NAME'));
    if ($exists) {
        $_POST['MESSAGE'] = 'This team name already exists!';
        return true;
    }
}

function createTeam(){
    $team = new Team();
    $team->user_id = $_SESSION['LOGGED_IN_ID'];
    $team->team_name = $_POST['TEAM_NAME'];
    if (isset($_POST['IMAGE_URL']))
    {
        $team->logo = $_POST['IMAGE_URL'];
    } else {
        $team->logo = "../sugimori/25.png";
    }
    $team->save();
    $_SESSION['TEAM_ID'] = $team->id;
    header('Location: /add-members');
}

function deleteTeam() 
{
    $deleteArray = Input::get('TEAM_DELETED');
    if ($deleteArray) 
    {
        deleteTeamAndMembers();
        $_POST['MESSAGE'] = "This team has been permenantly deleted.";
        header('Location: /view-teams');
    }
}

function deleteTeamAndMembers()
{
    $team = new Team();
        $teamMembers = new TeamMember();
        $team->attributes = Team::findByTeamId($_SESSION['TEAM_ID']);
        $teamMembers = TeamMember::findByTeamId($_SESSION['TEAM_ID']);
        foreach ($teamMembers->members as $teamMember)
        {
            $member = new TeamMember();
            $member->id = $teamMember['id'];
            $member->delete();
        }
        $team->delete();
}

function addMember()
{
    $membersArray = Input::get('member');
    if ($membersArray) {
        foreach ($membersArray as $member) 
        {
            $pokemon = Pokemon::getPokemon($member);
            $teamMember = new TeamMember();
            $teamMember->team_id = $_SESSION['TEAM_ID'];
            $teamMember->pokedex_id = $pokemon['id'];
            $teamMember->save();   
        }
    $_POST['MESSAGE'] = "Team successfully updated.";
    } else {
        $_POST['MESSAGE'] = "Search for Pokemon by Name, or enter Pokedex Number.";
    }
}

function loginController() 
{
    if (isset($_POST)) {  
        $user = Auth::attempt(Input::get('username'), Input::get('password'));
    }
    if ($user) {
        header('Location: /home');
    }
}

function signupController() 
{
    if (!empty($_POST['new_user'])) {
        $_POST['username'] = Input::get('new_user');
        $exists = User::findByUsername(Input::get('username'));
        if ($exists) {
            $_SESSION['SIGNUP_ERROR'] = 'This username already exists!';
            return false;
        }
        createAccount();
    }
}

function createAccount()
{
    $user = new User();

    $user->name = Input::get('new_user');
    $user->password = Input::get('password');

    $user->save();
    $_SESSION['SUCCESS_MESSAGE'] = 'Account created! Please log in below.';
    header('Location: /login');
}

// takes image from form submission and moves it into the uploads directory
function saveUploadedImage($input_name)
{
    $valid = true;

    // checks if $input_name is in the files super global
    if(isset($_FILES[$input_name]) && $_FILES[$input_name]['name'])
    {

        // checks if there are any errors on the upload from the submission
        if(!$_FILES[$input_name]['error'])
        {

            $tempFile = $_FILES[$input_name]['tmp_name'];
                $image_url = '/img/uploads' . $input_name;
                move_uploaded_file($tempFile, __DIR__ .'/../public' . $image_url);
                return $image_url;
        }

    }
    return null;
}
