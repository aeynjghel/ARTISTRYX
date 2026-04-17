import requests

url = "http://localhost/ARTISTRYX/src/service.php"

def register():
    print()
    print("  +-----------------------------------------+")
    print("  |              REGISTER                   |")
    print("  +-----------------------------------------+")

    while True:
        username = input("  Username : ").strip()
        email = input("  Email    : ").strip()
        password = input("  Password : ").strip()

        data = {
            "action": "register",
            "username": username,
            "email": email,
            "password": password
        }

        try:
            r = requests.post(url, json=data)
            res = r.json()
        except:
            print("  >> Server error or cannot connect.")
            return

        code = res.get("code")
        message = res.get("message", "No message from server")

        print(f"  >>  {message}")

        if code == 201:
            return

        elif code in (400, 409):
            continue

        elif code == 500:
            return
        
def login():
    print()
    print("  +-----------------------------------------+")
    print("  |                LOGIN                    |")
    print("  +-----------------------------------------+")

    while True:
        email = input("  Email    : ").strip()
        password = input("  Password : ").strip()

        data = {
            "action": "login",
            "email": email,
            "password": password
        }

        try:
            r = requests.post(url, json=data)
            res = r.json()
        except:
            print("  >> Server error or cannot connect.")
            return

        code = res.get("code")
        message = res.get("message", "No message from server")

        print(f"  >>  {message}")

        if code == 200:
            user = res.get("user", {})
            print()
            print("  Welcome back, " + user.get("username", "User") + "!")
            user_menu(user.get("id"))
            return

        elif code in (400, 401, 404):
            continue

        elif code == 500:
            return
        
def user_menu(userId):
    while True:
        print()
        print("  +=========================================+")
        print("  |               USER MENU                 |")
        print("  +=========================================+")
        print("  |  1. Register Shop                       |")
        print("  |  2. View Shop                           |")
        print("  |  3. Update Shop                         |")
        print("  |  4. Logout                              |")
        print("  +=========================================+")

        choice = input("  Choice : ")

        if choice == "1":
            create_shop(userId)
        elif choice == "2":
            view_shop(userId)
        elif choice == "3":
            update_shop(userId)
        elif choice == "4":
            print("  >> Logged out. See you next time!")
            break
        else:
            print("  >> Invalid choice. Try again.")


def create_shop(userId):
    print()
    print("  +-----------------------------------------+")
    print("  |             CREATE SHOP                 |")
    print("  +-----------------------------------------+")

    try:
        r = requests.get(url + "?userId=" + str(userId))
        res = r.json()
    except:
        print("  >> Server error.")
        return

    code = res.get("code")
    message = res.get("message", "No message from server")

    print("  >> " + message)
    
    if code != 200:
        return
    
    if res.get("hasShop") is True:
        return

    while True:
        shopName = input("  Shop Name : ").strip()
        if shopName == "":
            print("  >> Shop name cannot be empty.")
            continue
        break

    data = {
        "userId": userId,
        "shopName": shopName
    }

    try:
        r = requests.post(url, json=data)
        res = r.json()
    except:
        print("  >> Server error.")
        return

    code = res.get("code")
    message = res.get("message", "No message")

    print("  >> " + message)

    if code in (201, 409):
        return
 
def view_shop(userId):
    print()
    print("  +-----------------------------------------+")
    print("  |              VIEW SHOP                  |")
    print("  +-----------------------------------------+")

    try:
        r = requests.get(url + "?userId=" + str(userId))
        res = r.json()
    except:
        print("  >> Server error.")
        return

    code = res.get("code")
    message = res.get("message", "No message from server")

    print("  >> " + message)

    if code == 200 and "details" in res:
        d = res["details"]
        print("    Username  : " + d["username"])
        print("    Shop Name : " + d["shopName"])
        print("    Status    : " + d["shopStatus"])
        print("  +-----------------------------------------+")


def update_shop(userId):
    print()
    print("  +-----------------------------------------+")
    print("  |             UPDATE SHOP                 |")
    print("  +-----------------------------------------+")

    try:
        r   = requests.get(url + "?userId=" + str(userId))
        res = r.json()
    except:
        print("  >> Server error.")
        return

    code = res.get("code")
    message = res.get("message", "No message from server")

    print("  >> " + message)

    if code != 200 or "details" not in res:
        return

    print("  Current Shop Name : " + res["details"]["shopName"])

    while True:
        newName = input("  New Shop Name     : ").strip()
        if newName == "":
            print("  >> Shop name cannot be empty.")
            continue
        break

    data = {
        "userId"      : userId,
        "newShopName" : newName
    }

    try:
        r   = requests.put(url, json=data)
        res = r.json()
    except:
        print("  >> Server error.")
        return

    print("  >> " + res.get("message", "No message from server"))

while True:
    print()
    print("  +=========================================+")
    print("  |         Welcome to Artistryx!           |")
    print("  +=========================================+")
    print("  |                MAIN MENU                |")
    print("  +=========================================+")
    print("  |  1. Register                            |")
    print("  |  2. Login                               |")
    print("  |  3. Exit                                |")
    print("  +=========================================+")

    choice = input("  Choice : ")

    if choice == "1":
        register()
    elif choice == "2":
        login()
    elif choice == "3":
        print()
        print("  Goodbye! See you around ~")
        break
    else:
        print("  >> Invalid choice. Try again.")
