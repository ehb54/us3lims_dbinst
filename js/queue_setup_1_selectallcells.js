function SelectAllCells(){
    options = document.getElementById("cells");
    options[0].selected = false;
    for (i=1; i < options.length; i++)
    {
        options[i].selected = true;
    }
}
