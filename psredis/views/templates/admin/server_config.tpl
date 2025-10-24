<div class="panel">
    <div class="panel-heading"><i class="icon-server"></i> {l s='Konfiguracja Serwerów Redis' mod='psredis'}</div>
    
    <div class="form-wrapper">
        <form method="post" action="{$current_index|escape:'htmlall':'UTF-8'}&token={$token|escape:'htmlall':'UTF-8'}" class="defaultForm form-horizontal">
            <input type="hidden" name="submitPsredisServer" value="1" />
            
            {* Lista skonfigurowanych serwerów *}
            <table class="table" style="width: 80%; margin: 20px auto;">
                <thead>
                    <tr>
                        <th>{l s='Nazwa' mod='psredis'}</th>
                        <th>{l s='Host/Socket' mod='psredis'}</th>
                        <th>{l s='Port' mod='psredis'}</th>
                        <th>{l s='Sterownik' mod='psredis'}</th>
                        <th>{l s='Akcje' mod='psredis'}</th>
                    </tr>
                </thead>
                <tbody>
                    {if $psredis_connections}
                        {foreach from=$psredis_connections key=name item=conn}
                            <tr>
                                <td>{$name|escape:'htmlall':'UTF-8'}</td>
                                <td>{$conn.host|escape:'htmlall':'UTF-8'}{if $conn.socket} ({l s='Gniazdo Unix' mod='psredis'}){/if}</td>
                                <td>{$conn.port|escape:'htmlall':'UTF-8'}</td>
                                <td>{$conn.driver|escape:'htmlall':'UTF-8'}</td>
                                <td>
                                    <button type="button" class="btn btn-default btn-xs">{l s='Test' mod='psredis'}</button>
                                    <button type="button" class="btn btn-danger btn-xs">{l s='Usuń' mod='psredis'}</button>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="5">{l s='Brak skonfigurowanych serwerów. Dodaj nowy serwer poniżej.' mod='psredis'}</td>
                        </tr>
                    {/if}
                </tbody>
            </table>
            
            <hr />
            
            <h3>{l s='Dodaj/Edytuj Serwer' mod='psredis'}</h3>
            {* Pola formularza do dodawania serwera *}
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Nazwa Serwera' mod='psredis'}</label>
                <div class="col-lg-5"><input type="text" name="redis_server_name" required="required" /></div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Host' mod='psredis'}</label>
                <div class="col-lg-5"><input type="text" name="redis_host" value="127.0.0.1" required="required" /></div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Port' mod='psredis'}</label>
                <div class="col-lg-5"><input type="number" name="redis_port" value="6379" required="required" /></div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Gniazdo Unix (Opcjonalnie)' mod='psredis'}</label>
                <div class="col-lg-5"><input type="text" name="redis_socket" placeholder="/var/run/redis/redis.sock" /></div>
                <div class="col-lg-4"><p class="help-block">{l s='Przygotowanie konfiguracji gniazda unix dla mniejszych opóźnień.' mod='psredis'}</p></div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Sterownik' mod='psredis'}</label>
                <div class="col-lg-5">
                    <select name="redis_driver">
                        <option value="phpredis">{l s='PHPRedis (rekomendowany - wymaga rozszerzenia)' mod='psredis'}</option>
                        <option value="predis">{l s='Predis (przez Composer - dla braku rozszerzenia)' mod='psredis'}</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Hasło (AUTH)' mod='psredis'}</label>
                <div class="col-lg-5"><input type="password" name="redis_password" /></div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Baza Danych (DB)' mod='psredis'}</label>
                <div class="col-lg-5"><input type="number" name="redis_database" value="0" /></div>
            </div>
            
            <div class="panel-footer">
                <button type="submit" name="submitPsredisServer" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Zapisz Konfigurację Serwera' mod='psredis'}
                </button>
            </div>
            
        </form>
    </div>
</div>