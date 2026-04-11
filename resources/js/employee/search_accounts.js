function escHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

document.addEventListener("DOMContentLoaded", () => {

  const form = document.getElementById("reservationForm");

  const searchInput = document.getElementById("account_search");
  const resultsBox = document.getElementById("account_results");

  const selectedUserInput = document.getElementById("selected_user_id");
  const selectedName = document.getElementById("selected_account_name");
  const selectedBox = document.getElementById("selected_account_box");

  const errorMessage = document.getElementById("account_error");

  if(!searchInput) return;

  /* ============================
     ACCOUNT SEARCH
  ============================ */

  searchInput.addEventListener("keyup", async function(){

      const query = this.value.trim();

      // clear selected user if typing again
      selectedUserInput.value = "";
      selectedBox.style.display = "none";

      if(query.length < 2){
          resultsBox.innerHTML = "";
          return;
      }

      try{

          const response = await fetch(`/employee/search-accounts?search=${query}`);
          const users = await response.json();

          resultsBox.innerHTML = "";

          if(users.length === 0){

              resultsBox.innerHTML = `
                  <div class="account-result-item" style="cursor:default;">
                      No accounts found
                  </div>
              `;
              return;
          }

          users.forEach(user => {

              const item = document.createElement("div");
              item.classList.add("account-result-item");

              item.innerHTML = `
                  <strong>${escHtml(user.name)}</strong><br>
                  <small>${escHtml(user.email)}</small>
              `;

              item.addEventListener("click", () => {

                  /* SET SELECTED ACCOUNT */

                  selectedUserInput.value = user.id;
                  searchInput.value = user.name;

                  selectedName.textContent = user.name;
                  selectedBox.style.display = "block";

                  resultsBox.innerHTML = "";

                  errorMessage.style.display = "none";

              });

              resultsBox.appendChild(item);

          });

      }catch(error){
          console.error("Search error:", error);
      }

  });


  /* ============================
     CLEAR ERROR WHEN TYPING
  ============================ */

  searchInput.addEventListener("input", () => {

      errorMessage.style.display = "none";

  });


  /* ============================
     FORM VALIDATION
  ============================ */

  form?.addEventListener("submit", function(e){

      const typedValue = searchInput.value.trim();
      const selectedValue = selectedUserInput.value;

      errorMessage.style.display = "none";

      /* NOTHING TYPED */

      if(typedValue === ""){
          e.preventDefault();

          errorMessage.textContent = "Please search and select a client account.";
          errorMessage.style.display = "block";
          return;
      }

      /* TYPED BUT NOT SELECTED */

      if(selectedValue === ""){
          e.preventDefault();

          errorMessage.textContent = "Account does not exist. Please select from the search results.";
          errorMessage.style.display = "block";
          return;
      }

  });

});