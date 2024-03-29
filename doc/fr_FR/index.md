# Eco 2 Watt

## Changelog

[Accés changelog](changelog.md)

## Description

Ce plugin permet de récupérer les informations des sites EcoWatt, EJP et Tempo (EDF), notamment dans l’optique de s’en servir (dans des scénarios) pour réduire ses consommations électriques en cas de fortes tensions sur le réseau électrique ou lors des jours EJP (selon les contrats d’électricité souscrits).

## Configuration

### Configuration du plugin

Après téléchargement du plugin, il vous suffit juste d'activer celui-ci, il n'y a aucune configuration à ce niveau.

### Configuration des équipements

La configuration des équipements Eco 2 Watt est accessible à partir du menu Plugins puis energie : 

Vous retrouvez ici toute la configuration de votre équipement : 

- *Nom de l'équipement* : nom de votre équipement Eco 2 Watt,
- *Objet parent* : indique l'objet parent auquel appartient l'équipement,
- *Activer* : permet de rendre votre équipement actif,
- *Visible* : rend votre équipement visible sur le dashboard.

Ensuite, vous avez deux autres paramètres à configurer :

- *Type de source de données* : EJP ou EcoWatt,
- *Région* : afin de préciser les informations de la région que vous souhaitez récupérer. En fonction du choix fait sur le type de source de données, le choix des régions n'est pas le même, puisque chacun d'eux s'applique sur des régions différentes.
  - Les régions pour EcoWatt sont : Bretagne ou Provence-Alpes-Côtes d'Azur,
  - Les régions EJP sont : Zone Nord, Zone Sud, Zone Ouest, Zone Provence-Alpes-Côtes d'Azur.


En-dessous vous retrouvez la liste des commandes : 

- *Nom* : le nom de la commande affichée sur le dashboard,
- *Paramètres* : type de commande,
- *Roues crantées* : pour les paramètres avancés de la commande,
- *Tester* : permet de tester la commande,
- *Supprimer* (signe -) : permet de supprimer la commande.

Les boutons "Afficher" et "Historiser" sont les boutons habituels d'affichage (ou non) de la commande sur le widget et de son historisation (ou non).

#### Commandes EcoWatt

Pour EcoWatt, 2 commandes existent :

- Aujourd'hui : permet de savoir la tendance pour le jour même quant à la tension sur le réseau électrique,
- Demain : permet de savoir la tendance pour le lendemain quant à la tension sur le réseau électrique.

Pour ces deux commandes les valeurs peuvent être :

- Verte : la tendance est verte ("tout va bien"),
- Orange : la tendance est orange (il faut faire attention à ses consommations),
- Rouge : la tendance est rouge (il faut réduire ses consommations afin d'éviter une coupure généralisée).

#### Commandes EJP

Pour EJP, 4 commandes existent :

- Jours EJP restants : indique le nombre de jours EJP restants pour la période EJP (1er novembre - 31 mars) en cours,
- Total de jours EJP : indique le nombre total de jours EJP prévus pour la période EJP (1er novembre - 31 mars) en cours.
- Aujourd'hui : permet de savoir si la date du jour est en EJP ou pas,
- Demain : permet de savoir si le lendemain est en EJP ou pas.

Pour ces deux dernieres commandes les valeurs peuvent être :

- Pas d'EJP : la date du jour n'est pas en EJP
- EJP : la date du jour est en EJP
- Non déterminé : le site n'a pas communiqué l'information



Pour ces deux commandes les valeurs sont des nombres.

#### Commandes Tempo

Pour Tempo, 8 commandes existent :

- Aujourd'hui : permet de connaître la couleur du jour,
- Demain : permet de connaître la couleur du lendemain.

Pour ces deux commandes les valeurs peuvent être :

- BLEU : le jour est "bleu",
- BLANC : le jour est "blanc",
- ROUGE : le jour est "rouge".

Pour chaque couleur de jour (Bleu - Blanc - Rouge) :

- Jours restants : indique le nombre de jours de cette couleur restants pour la période Tempo en cours,
- Total de jours EJP : indique le nombre total de jours de cette couleur prévus pour la période Tempo en cours.


#### Widgets

Les couleurs changent en fonction de la tendance (pour un équipement EcoWatt) et en fonction de l'état EJP (pour un équipement EJP).

## FAQ
