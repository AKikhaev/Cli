#mcedit ~/.bash_aliases

function acli() {
    local PWD=$(pwd -P)
    if  [[ -f "$PWD/acli" ]] ; then
        php acli "$@"
    fi
}

function _acli_complete_()
{
    local PWD=$(pwd -P)
    if  [[ -f "$PWD/acli" ]] ; then
        #local pwds
        #IFS='/' read -r -a pwds <<< "$PWD"
        ##mapfile -d / -t pwds <<<"$PWD/"
        #projectName=${pwds[3]}

        local cmd="${1##*/}"
        local cur_word="${COMP_WORDS[COMP_CWORD]}"
        local prev_word="${COMP_WORDS[COMP_CWORD-1]}"
        local line_full=${COMP_LINE}
        local line=$(printf " %s" "${COMP_WORDS[@]:1}"); line=${line:1}
        local suggestAcli=$(php acli $line --silence_greetings --bash_completion_cword=$cur_word --cword_num=$COMP_CWORD)
        COMPREPLY=($(compgen -W "$suggestAcli" -- $cur_word))
    else
        COMPREPLY=()
    fi
}

complete -F _acli_complete_ acli

