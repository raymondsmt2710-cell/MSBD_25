<style>
    @media(min-width:641px){
        .nav-inner{display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr)}
        .nav-inner > .brand{grid-column:1;justify-self:start;min-width:0}

        .nav-inner > .brand > div{min-width:0}
        .nav-inner > .brand .nama{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .nav-inner > .nav-links{grid-column:2;justify-self:center}
        .nav-inner > .nav-aksi{grid-column:3;justify-self:end}

        .nav-inner > .nav-profil{grid-column:3;justify-self:end}
    }


    @media(min-width:641px) and (max-width:900px){
        .nav-inner > .brand .sub{display:none}
    }
    @media(min-width:641px) and (max-width:760px){
        .nav-inner > .nav-links a{padding:7px 8px}
    }
</style>
